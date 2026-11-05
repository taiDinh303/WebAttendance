<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyAccountEmail;
use App\Support\GoogleIdentityValidator;
use Google\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private array $claims;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config(['services.google.client_id' => 'synthetic.apps.googleusercontent.com', 'demo.portfolio_enabled' => false]);
        $this->claims = ['sub' => 'google-student', 'email' => 'student@gmail.com', 'email_verified' => true,
            'name' => 'Google Student', 'aud' => config('services.google.client_id'), 'iss' => 'https://accounts.google.com',
            'exp' => time() + 3600, 'iat' => time()];
        $client = \Mockery::mock(Client::class);
        $client->shouldReceive('setClientId');
        $client->shouldReceive('verifyIdToken')->andReturnUsing(fn () => $this->claims);
        $this->app->bind(GoogleIdentityValidator::class, fn () => new GoogleIdentityValidator($client));
    }

    private function challenge(): void
    {
        $nonce = $this->postJson('/api/v1/auth/google/challenge')->assertOk()->json('data.nonce');
        $this->claims['nonce'] = $nonce;
    }

    public function test_new_google_registration_uses_selected_role_and_returning_login_cannot_change_it(): void
    {
        $this->challenge();
        $id = $this->postJson('/api/v1/auth/google', ['credential' => 'synthetic', 'role' => 'student'])
            ->assertOk()->assertJsonPath('data.role', 'student')->assertJsonPath('data.emailVerified', true)
            ->assertJsonPath('data.hasPassword', false)->assertJsonPath('data.googleLinked', true)->json('data.id');
        $this->assertNull(User::findOrFail($id)->password);
        $this->getJson('/api/v1/me')->assertOk()->assertDontSee('google_subject');
        $this->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->challenge();
        $this->postJson('/api/v1/auth/google', ['credential' => 'synthetic', 'role' => 'teacher'])
            ->assertOk()->assertJsonPath('data.role', 'student')->assertJsonPath('data.id', $id);
        Notification::assertNothingSent();
    }

    public function test_existing_email_requires_explicit_link_and_link_targets_only_the_current_account(): void
    {
        $user = User::factory()->create(['email' => 'student@gmail.com']);
        $this->challenge();
        $this->postJson('/api/v1/auth/google', ['credential' => 'synthetic', 'role' => 'teacher'])
            ->assertConflict()->assertJsonPath('code', 'GOOGLE_LINK_REQUIRED');
        $this->assertNull($user->fresh()->google_subject);
        $this->actingAs($user);
        $this->challenge();
        $this->postJson('/api/v1/me/google', ['credential' => 'synthetic'])->assertOk()->assertJsonPath('data.googleLinked', true);
        $this->deleteJson('/api/v1/me/google')->assertOk()->assertJsonPath('data.googleLinked', false);
        $this->assertSame(1, User::count());
    }

    public function test_google_only_account_cannot_disconnect_and_password_login_fails_safely(): void
    {
        $user = User::factory()->create(['google_subject' => 'google-student', 'password' => null]);
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'NotARealPass123!'])
            ->assertUnprocessable()->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $this->actingAs($user)->deleteJson('/api/v1/me/google')->assertConflict()->assertJsonPath('code', 'PASSWORD_REQUIRED');
        $this->assertSame('google-student', $user->fresh()->google_subject);
    }

    public function test_unknown_user_requires_registration_role_and_admin_is_never_accepted(): void
    {
        $this->challenge();
        $this->postJson('/api/v1/auth/google', ['credential' => 'synthetic'])->assertConflict()->assertJsonPath('code', 'GOOGLE_REGISTRATION_REQUIRED');
        $this->postJson('/api/v1/auth/google', ['credential' => 'synthetic', 'role' => 'admin'])->assertUnprocessable();
        $this->assertSame(0, User::count());
    }

    public function test_nonce_is_required_expiring_and_one_time(): void
    {
        $this->postJson('/api/v1/auth/google', ['credential' => 'synthetic'])->assertUnprocessable()->assertJsonPath('code', 'GOOGLE_CHALLENGE_EXPIRED');
        $this->withSession(['google_challenge' => ['nonce' => 'expired', 'expires' => time() - 1]])
            ->postJson('/api/v1/auth/google', ['credential' => 'synthetic'])->assertUnprocessable();
        $this->challenge();
        $this->postJson('/api/v1/auth/google', ['credential' => 'synthetic'])->assertConflict();
        $this->postJson('/api/v1/auth/google', ['credential' => 'synthetic'])->assertUnprocessable()->assertJsonPath('code', 'GOOGLE_CHALLENGE_EXPIRED');
    }

    public function test_locked_google_account_and_mismatched_link_are_rejected(): void
    {
        User::factory()->create(['google_subject' => 'google-student', 'account_status' => 'locked']);
        $this->challenge();
        $this->postJson('/api/v1/auth/google', ['credential' => 'synthetic'])->assertForbidden()->assertJsonPath('code', 'ACCOUNT_LOCKED');
        $other = User::factory()->create(['email' => 'other@example.test']);
        $this->actingAs($other);
        $this->challenge();
        $this->postJson('/api/v1/me/google', ['credential' => 'synthetic'])->assertUnprocessable()->assertJsonPath('code', 'GOOGLE_EMAIL_MISMATCH');
        $this->assertNull($other->fresh()->google_subject);
    }

    public function test_third_party_google_email_requires_our_email_verification(): void
    {
        $this->claims['email'] = 'student@example.test';
        $this->challenge();
        $id = $this->postJson('/api/v1/auth/google', ['credential' => 'synthetic', 'role' => 'teacher'])
            ->assertOk()->assertJsonPath('data.emailVerified', false)->json('data.id');
        Notification::assertSentTo(User::findOrFail($id), VerifyAccountEmail::class);
        $this->getJson('/api/v1/dashboard')->assertForbidden();
    }

    public function test_disabled_configuration_fails_closed(): void
    {
        config(['services.google.client_id' => null]);
        $this->postJson('/api/v1/auth/google/challenge')->assertServiceUnavailable()->assertJsonPath('code', 'GOOGLE_DISABLED');
        $this->postJson('/api/v1/me/google', ['credential' => 'synthetic'])->assertUnauthorized();
    }
}
