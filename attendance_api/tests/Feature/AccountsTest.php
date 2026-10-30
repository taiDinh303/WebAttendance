<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetAccountPassword;
use App\Notifications\VerifyAccountEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class AccountsTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $extra = []): array
    {
        return array_replace(['displayName' => 'Synthetic Teacher', 'email' => 'Teacher@Example.test', 'password' => 'SyntheticPass123!', 'passwordConfirmation' => 'SyntheticPass123!', 'role' => 'teacher'], $extra);
    }

    public function test_registration_normalizes_email_hashes_password_and_requires_verification(): void
    {
        Notification::fake();
        $response = $this->postJson('/api/v1/auth/register', $this->registration())->assertCreated()->assertJsonPath('data.role', 'teacher')->assertJsonPath('data.email', 'teacher@example.test')->assertJsonPath('data.emailVerified', false);
        $user = User::findOrFail($response->json('data.id'));
        $this->assertTrue(Hash::check('SyntheticPass123!', $user->password));
        Notification::assertSentTo($user, VerifyAccountEmail::class);
        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('auth_version', $response->json('data'));
        $this->actingAs($user)->postJson('/api/v1/classes', ['name' => 'No access yet'])->assertForbidden()->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_registration_cannot_grant_admin_or_verified_status(): void
    {
        Notification::fake();
        $this->postJson('/api/v1/auth/register', $this->registration(['role' => 'admin']))->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->postJson('/api/v1/auth/register', $this->registration(['emailVerifiedAt' => now()->toISOString()]))->assertUnprocessable();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_normalized_duplicate_email_is_rejected(): void
    {
        Notification::fake();
        $this->postJson('/api/v1/auth/register', $this->registration())->assertCreated();
        $this->postJson('/api/v1/auth/register', $this->registration(['email' => 'teacher@example.test']))->assertUnprocessable();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_me_and_logout_use_a_session_and_no_bearer_token(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'SyntheticPass123!'])->assertOk()->assertJsonPath('data.id', $user->id)->assertJsonMissingPath('data.token');
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.id', $user->id);
        $this->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_invalid_credentials_are_generic_and_locked_accounts_cannot_log_in(): void
    {
        $user = User::factory()->create(['account_status' => 'locked']);
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'bad'])->assertStatus(422)->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $this->postJson('/api/v1/auth/login', ['email' => 'absent@example.test', 'password' => 'bad'])->assertStatus(422)->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'SyntheticPass123!'])->assertForbidden()->assertJsonPath('code', 'ACCOUNT_LOCKED');
    }

    public function test_signed_verification_is_bound_to_authenticated_user_and_expiry(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->getJson($url)->assertOk();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $other = User::factory()->unverified()->create();
        $this->actingAs($other)->getJson($url)->assertForbidden();
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $other->id, 'hash' => sha1($other->email)]);
        $this->getJson($expired)->assertForbidden();
        $this->assertFalse($other->fresh()->hasVerifiedEmail());
    }

    public function test_optional_profile_details_are_validated_and_saved(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->patchJson('/api/v1/me/profile', ['gender' => 'female', 'birthday' => '2000-01-02', 'avatarKey' => 'green', 'expectedVersion' => 1])->assertOk()->assertJsonPath('data.gender', 'female')->assertJsonPath('data.avatarKey', 'green')->assertJsonPath('data.birthday', '2000-01-02');
        $this->patchJson('/api/v1/me/profile', ['gender' => 'invalid', 'avatarKey' => '../file', 'birthday' => '2999-01-01', 'expectedVersion' => 2])->assertUnprocessable();
        $this->assertSame(2, $user->fresh()->version);
    }

    public function test_profile_is_self_only_and_uses_optimistic_version(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->patchJson('/api/v1/me/profile', ['displayName' => 'Updated', 'expectedVersion' => 1])->assertOk()->assertJsonPath('data.version', 2);
        $this->patchJson('/api/v1/me/profile', ['displayName' => 'Overwrite', 'expectedVersion' => 1])->assertConflict();
        $this->patchJson('/api/v1/me/profile', ['role' => 'admin', 'userId' => $other->id, 'expectedVersion' => 2])->assertUnprocessable();
        $this->assertSame('Updated', $user->fresh()->display_name);
        $this->assertSame('student', $other->fresh()->role);
    }

    public function test_forgot_password_does_not_disclose_account_and_reset_token_is_single_use(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $existing = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertAccepted();
        $missing = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'missing@example.test'])->assertAccepted();
        $this->assertSame($existing->json('data'), $missing->json('data'));
        Notification::assertSentTo($user, ResetAccountPassword::class);
        $token = Password::createToken($user);
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'ChangedPass456!', 'passwordConfirmation' => 'ChangedPass456!'];
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertNoContent();
        $this->assertTrue(Hash::check('ChangedPass456!', $user->fresh()->password));
        $this->assertSame(2, $user->fresh()->auth_version);
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable()->assertJsonPath('code', 'INVALID_RESET_TOKEN');
    }

    public function test_password_change_requires_proof_and_revokes_previous_session_version(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['authVersion' => 1])->postJson('/api/v1/me/change-password', ['currentPassword' => 'wrong', 'password' => 'ChangedPass456!', 'passwordConfirmation' => 'ChangedPass456!'])->assertUnprocessable();
        $this->postJson('/api/v1/me/change-password', ['currentPassword' => 'SyntheticPass123!', 'password' => 'ChangedPass456!', 'passwordConfirmation' => 'ChangedPass456!'])->assertNoContent();
        $this->getJson('/api/v1/me')->assertOk();
        $this->withSession(['authVersion' => 1])->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_lock_is_enforced_for_an_already_authenticated_account(): void
    {
        $user = User::factory()->create(['account_status' => 'locked']);
        $this->actingAs($user)->getJson('/api/v1/me')->assertForbidden()->assertJsonPath('code', 'ACCOUNT_LOCKED');
    }

    public function test_errors_have_trace_ids_and_do_not_expose_debug_information(): void
    {
        $response = $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonStructure(['code', 'message', 'traceId']);
        $this->assertNotEmpty($response->json('traceId'));
        $this->assertArrayNotHasKey('exception', $response->json());
        $this->getJson('/api/v1/does-not-exist')->assertNotFound()->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_login_has_a_rate_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'rate@example.test', 'password' => 'wrong'])->assertUnprocessable();
        }
        $this->postJson('/api/v1/auth/login', ['email' => 'rate@example.test', 'password' => 'wrong'])->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('code', 'RATE_LIMITED');
    }
}
