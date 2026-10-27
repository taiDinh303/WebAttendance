<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::find($request->user()->id);
        if (! $user) {
            throw new ApiException('UNAUTHENTICATED', 'Please sign in again.', 401);
        }
        if ($user->account_status !== 'active') {
            throw new ApiException('ACCOUNT_LOCKED', 'This account is locked.', 403);
        }
        $version = $request->session()->get('authVersion');
        if ($version !== null && (int) $version !== $user->auth_version) {
            Auth::guard('web')->logout();
            Auth::guard('sanctum')->forgetUser();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw new ApiException('UNAUTHENTICATED', 'Please sign in again.', 401);
        }
        $request->session()->put('authVersion', $user->auth_version);
        Auth::guard('web')->setUser($user);
        Auth::guard('sanctum')->setUser($user);

        return $next($request);
    }
}
