<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireVerifiedEmail
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->hasVerifiedEmail()) {
            throw new ApiException('EMAIL_NOT_VERIFIED', 'Verify your email before using the workspace.', 403);
        }

        return $next($request);
    }
}
