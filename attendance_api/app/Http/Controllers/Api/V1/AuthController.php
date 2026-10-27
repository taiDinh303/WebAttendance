<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Accounts\RegisterAccount;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

final class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterAccount $action): JsonResponse
    {
        return ApiResponse::success(new UserResource($action->handle($request->validated())), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw new ApiException('INVALID_CREDENTIALS', 'Invalid email or password.', 422);
        }
        if ($user->account_status !== 'active') {
            throw new ApiException('ACCOUNT_LOCKED', 'This account is locked.', 403);
        }
        if (Hash::needsRehash($user->password)) {
            $user->password = $data['password'];
            $user->save();
        }
        Auth::guard('web')->login($user, $data['rememberMe'] ?? false);
        $request->session()->regenerate();
        $request->session()->put('authVersion', $user->auth_version);

        return ApiResponse::success(new UserResource($user));
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        Auth::guard('sanctum')->forgetUser();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
