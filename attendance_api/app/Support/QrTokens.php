<?php

namespace App\Support;

use App\Exceptions\ApiException;
use App\Models\AttendanceWindow;
use Carbon\CarbonImmutable;

final class QrTokens
{
    public function issue(AttendanceWindow $window): array
    {
        $slot = intdiv(now()->getTimestamp(), 30) * 30;
        $expiry = min($slot + 60, $window->closes_at->getTimestamp());
        $data = ['windowId' => $window->id, 'version' => $window->token_version, 'issuedAt' => $slot, 'expiresAt' => $expiry];
        $payload = $this->encode(json_encode($data, JSON_THROW_ON_ERROR));
        $signature = $this->encode(hash_hmac('sha256', $payload, config('app.key'), true));

        return ['qrToken' => $payload.'.'.$signature, 'validUntil' => CarbonImmutable::createFromTimestampUTC($expiry)->toISOString(), 'rotatesAt' => CarbonImmutable::createFromTimestampUTC(min($slot + 30, $window->closes_at->getTimestamp()))->toISOString(), 'serverTime' => now()->toISOString()];
    }

    /** Signature verification only; authorization and time checks run after resource scoping. */
    public function read(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2 || ! hash_equals($this->encode(hash_hmac('sha256', $parts[0], config('app.key'), true)), $parts[1])) {
            throw new ApiException('INVALID_QR_TOKEN', 'The QR code is invalid.', 422);
        }
        $decoded = base64_decode(strtr($parts[0], '-_', '+/'), true);
        $data = $decoded === false ? null : json_decode($decoded, true);
        if (! is_array($data) || ! is_string($data['windowId'] ?? null) || ! is_int($data['version'] ?? null) || ! is_int($data['issuedAt'] ?? null) || ! is_int($data['expiresAt'] ?? null)) {
            throw new ApiException('INVALID_QR_TOKEN', 'The QR code is invalid.', 422);
        }

        return $data;
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
