<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Accounts\RevokeAccountSessions;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

final class PasswordController extends Controller
{
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->validated());

        return ApiResponse::success(['message' => 'If this account exists, a reset email will be sent.'], 202);
    }

    public function reset(ResetPasswordRequest $request, RevokeAccountSessions $revoke): Response
    {
        $data = $request->validated();
        $status = DB::transaction(function () use ($data, $revoke) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->lockForUpdate()->first();

            return Password::reset(['email' => $data['email'], 'token' => $data['token'], 'password' => $data['password'], 'password_confirmation' => $data['passwordConfirmation']],
                function (User $account, string $password) use ($revoke) {
                    DB::transaction(function () use ($account, $password, $revoke) {
                        $user = User::whereKey($account->id)->lockForUpdate()->firstOrFail();
                        $user->password = $password;
                        $revoke->handle($user);
                    });
                });
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw new ApiException('INVALID_RESET_TOKEN', 'The reset link is invalid or expired.', 422);
        }

        return response()->noContent();
    }

    public function change(ChangePasswordRequest $request, RevokeAccountSessions $revoke): Response
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $data, $revoke) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (! Hash::check($data['currentPassword'], $user->password)) {
                throw new ApiException('INVALID_PASSWORD', 'The current password is incorrect.', 422);
            }
            $user->password = $data['password'];
            $revoke->handle($user);
            $request->session()->regenerate();
            $request->session()->put('authVersion', $user->auth_version);
        });

        return response()->noContent();
    }
}
