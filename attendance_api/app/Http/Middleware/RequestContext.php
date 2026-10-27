<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class RequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = (string) Str::uuid();
        $request->attributes->set('traceId', $traceId);
        $response = $next($request);
        $response->headers->set('X-Request-ID', $traceId);
        if ($request->is('api/*') || $request->is('sanctum/*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
