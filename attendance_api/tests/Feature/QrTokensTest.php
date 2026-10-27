<?php

namespace Tests\Feature;

use App\Exceptions\ApiException;
use App\Models\AttendanceWindow;
use App\Support\QrTokens;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class QrTokensTest extends TestCase
{
    public function test_rotates_at_30_seconds_and_keeps_previous_token_readable_for_ttl(): void
    {
        $window = (new AttendanceWindow)->forceFill(['id' => 'window-test', 'closes_at' => '2026-10-28T03:00:00Z', 'token_version' => 7]);
        $tokens = new QrTokens;
        $this->travelTo(CarbonImmutable::parse('2026-10-28T02:00:00Z'));
        $first = $tokens->issue($window);
        $this->travelTo(CarbonImmutable::parse('2026-10-28T02:00:29.999999Z'));
        $sameSlot = $tokens->issue($window);
        $this->travelTo(CarbonImmutable::parse('2026-10-28T02:00:30Z'));
        $nextSlot = $tokens->issue($window);

        $this->assertSame($first['qrToken'], $sameSlot['qrToken']);
        $this->assertNotSame($first['qrToken'], $nextSlot['qrToken']);
        $this->assertSame('2026-10-28T02:00:30.000000Z', $first['rotatesAt']);
        $this->assertSame('2026-10-28T02:01:00.000000Z', $first['validUntil']);
        $this->assertSame('2026-10-28T02:01:30.000000Z', $nextSlot['validUntil']);
        $this->assertSame([
            'windowId' => 'window-test', 'version' => 7,
            'issuedAt' => CarbonImmutable::parse('2026-10-28T02:00:00Z')->getTimestamp(),
            'expiresAt' => CarbonImmutable::parse('2026-10-28T02:01:00Z')->getTimestamp(),
        ], $tokens->read($first['qrToken']));
    }

    public function test_caps_expiry_and_rotation_at_window_close(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-28T02:00:29Z'));
        $window = (new AttendanceWindow)->forceFill(['id' => 'window-test', 'closes_at' => '2026-10-28T02:00:30Z']);

        $issued = (new QrTokens)->issue($window);

        $this->assertSame('2026-10-28T02:00:30.000000Z', $issued['validUntil']);
        $this->assertSame($issued['validUntil'], $issued['rotatesAt']);
        $this->assertSame('2026-10-28T02:00:29.000000Z', $issued['serverTime']);
    }

    public function test_rejects_signed_token_after_signing_key_changes(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-28T02:00:00Z'));
        $token = (new QrTokens)->issue((new AttendanceWindow)->forceFill(['id' => 'window-test', 'closes_at' => '2026-10-28T03:00:00Z']))['qrToken'];
        config(['app.key' => 'different-test-signing-key']);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('The QR code is invalid.');

        (new QrTokens)->read($token);
    }

    #[DataProvider('invalidTokens')]
    public function test_rejects_malformed_or_tampered_claims(string $payload, bool $sign): void
    {
        $token = $payload;
        if ($sign) {
            $encoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
            $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $encoded, config('app.key'), true)), '+/', '-_'), '=');
            $token = $encoded.'.'.$signature;
        }

        try {
            (new QrTokens)->read($token);
        } catch (ApiException $exception) {
            $this->assertSame('INVALID_QR_TOKEN', $exception->errorCode);
            $this->assertSame(422, $exception->status);

            return;
        }
        $this->fail('Expected invalid QR token to be rejected.');
    }

    public static function invalidTokens(): array
    {
        return [
            'empty' => ['', false],
            'missing signature' => ['payload', false],
            'extra segment' => ['a.b.c', false],
            'forged signature' => ['e30.forged', false],
            'signed invalid JSON' => ['{', true],
            'signed scalar' => ['null', true],
            'signed missing claims' => ['{}', true],
            'signed wrong version type' => ['{"windowId":"w","version":"1","issuedAt":1,"expiresAt":60}', true],
            'signed wrong timestamp type' => ['{"windowId":"w","version":1,"issuedAt":1,"expiresAt":"60"}', true],
            'signed wrong resource type' => ['{"windowId":1,"version":1,"issuedAt":1,"expiresAt":60}', true],
        ];
    }
}
