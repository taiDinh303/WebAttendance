<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Accounts\GoogleAccount;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleAuthRequest;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use App\Support\GoogleIdentityValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class GoogleAuthController extends Controller
{
    public function challenge(Request $request): JsonResponse
    {
        if (! config('services.google.client_id')) {
            throw new ApiException('GOOGLE_DISABLED', 'Google sign-in is not configured.', 503);
        }
        $nonce = Str::random(48);
        $request->session()->put('google_challenge', ['nonce' => $nonce, 'expires' => time() + 300]);

        return ApiResponse::success(['nonce' => $nonce, 'clientId' => config('services.google.client_id')]);
    }

    public function login(GoogleAuthRequest $request, GoogleIdentityValidator $validator, GoogleAccount $accounts): JsonResponse
    {
        if (Auth::guard('web')->check()) {
            throw new ApiException('ALREADY_AUTHENTICATED', 'Sign out before signing in to another account.', 409);
        }
        $identity = $validator->validate($request->validated('credential'), $this->consumeNonce($request));
        $user = $accounts->login($identity, $request->validated('role'));
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('authVersion', $user->auth_version);

        return ApiResponse::success(new UserResource($user));
    }

    public function link(GoogleAuthRequest $request, GoogleIdentityValidator $validator, GoogleAccount $accounts): JsonResponse
    {
        $identity = $validator->validate($request->validated('credential'), $this->consumeNonce($request));

        return ApiResponse::success(new UserResource($accounts->link($request->user(), $identity)));
    }

    public function unlink(Request $request, GoogleAccount $accounts): JsonResponse
    {
        return ApiResponse::success(new UserResource($accounts->unlink($request->user())));
    }

    private function consumeNonce(Request $request): string
    {
        $challenge = $request->session()->pull('google_challenge');
        if (! is_array($challenge) || ($challenge['expires'] ?? 0) < time()
            || ! is_string($challenge['nonce'] ?? null)
            || ! Cache::add('google-used:'.hash('sha256', $challenge['nonce']), true, 600)) {
            throw new ApiException('GOOGLE_CHALLENGE_EXPIRED', 'Refresh the Google button and try again.', 422);
        }

        return $challenge['nonce'];
    }
}
