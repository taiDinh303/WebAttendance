<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetAccountPassword;
use App\Notifications\VerifyAccountEmail;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

final class BrevoTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['mail.default' => 'brevo', 'mail.mailers.brevo.key' => 'synthetic-key',
            'mail.from.address' => 'sender@example.test', 'mail.from.name' => 'AttendanceWeb']);
    }

    public function test_laravel_mail_is_sent_over_https_with_the_configured_sender(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'test-id'], 201)]);
        Mail::html('<p>Verification link</p>', fn ($mail) => $mail->to('student@example.test')->subject('Verify account'));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request->hasHeader('api-key', 'synthetic-key')
            && $request['sender'] === ['email' => 'sender@example.test', 'name' => 'AttendanceWeb']
            && $request['to'][0]['email'] === 'student@example.test'
            && $request['htmlContent'] === '<p>Verification link</p>');
        Http::assertSentCount(1);
    }

    public function test_missing_key_fails_before_any_request(): void
    {
        Http::fake();
        config(['mail.mailers.brevo.key' => '']);
        try {
            Mail::raw('test', fn ($mail) => $mail->to('student@example.test')->subject('Test'));
            $this->fail('Missing key must fail.');
        } catch (TransportException $exception) {
            $this->assertSame('Brevo API key is not configured.', $exception->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_verification_and_recovery_notifications_use_brevo(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'test-id'], 201)]);
        $user = new User(['email' => 'student@example.test', 'display_name' => 'Synthetic Student']);
        $user->id = '01K00000000000000000000000';
        Notification::sendNow($user, new VerifyAccountEmail);
        Notification::sendNow($user, new ResetAccountPassword('synthetic-token'));
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => str_contains($request['htmlContent'], 'synthetic-token')
            && $request['to'][0]['email'] === 'student@example.test');
    }

    public function test_provider_failure_is_not_silently_accepted(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'private provider response'], 401)]);
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Brevo did not confirm email acceptance (HTTP 401).');
        Mail::raw('test', fn ($mail) => $mail->to('student@example.test')->subject('Test'));
    }

    public function test_connection_failure_uses_a_safe_error(): void
    {
        Http::fake(['api.brevo.com/*' => Http::failedConnection()]);
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Brevo connection failed or timed out.');
        Mail::raw('test', fn ($mail) => $mail->to('student@example.test')->subject('Test'));
    }
}
