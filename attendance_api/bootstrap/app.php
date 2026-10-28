<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\RequestContext;
use App\Http\Middleware\RequireVerifiedEmail;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // This API has no server-rendered login route, including requests with Accept: */*.
        $middleware->redirectGuestsTo(null);
        $middleware->prepend(RequestContext::class);
        $middleware->alias([
            'account.active' => EnsureAccountActive::class,
            'email.verified' => RequireVerifiedEmail::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->dontReport([ApiException::class]);
        $exceptions->render(function (Throwable $error, Request $request) {
            if (! $request->is('api/*') && ! $request->is('health/*')) {
                return null;
            }
            $status = 500;
            $code = 'INTERNAL_ERROR';
            $message = 'An unexpected error occurred.';
            $fields = null;
            if ($error instanceof ApiException) {
                [$status, $code, $message] = [$error->status, $error->errorCode, $error->getMessage()];
            } elseif ($error instanceof ValidationException) {
                [$status, $code, $message, $fields] = [422, 'VALIDATION_ERROR', 'Please check the submitted fields.', $error->errors()];
            } elseif ($error instanceof AuthenticationException) {
                [$status, $code, $message] = [401, 'UNAUTHENTICATED', 'Please sign in.'];
            } elseif ($error instanceof AuthorizationException) {
                [$status, $code, $message] = [$error->hasStatus() ? $error->status() : 403, 'FORBIDDEN', 'This action is not allowed.'];
            } elseif ($error instanceof ModelNotFoundException) {
                [$status, $code, $message] = [404, 'NOT_FOUND', 'Resource not found.'];
            } elseif ($error instanceof TokenMismatchException) {
                [$status, $code, $message] = [419, 'CSRF_MISMATCH', 'Refresh the security cookie and try again.'];
            } elseif ($error instanceof UniqueConstraintViolationException) {
                [$status, $code, $message] = [409, 'CONFLICT', 'This record already exists.'];
            } elseif ($error instanceof HttpExceptionInterface) {
                $status = $error->getStatusCode();
                $code = match ($status) {
                    404 => 'NOT_FOUND', 403 => 'FORBIDDEN', 419 => 'CSRF_MISMATCH', 429 => 'RATE_LIMITED', 405 => 'METHOD_NOT_ALLOWED', default => 'HTTP_ERROR'
                };
                $message = match ($status) {
                    404 => 'Resource not found.', 429 => 'Too many requests. Please wait.', default => 'The request could not be completed.'
                };
            }
            $body = ['code' => $code, 'message' => $message, 'traceId' => $request->attributes->get('traceId')];
            if ($fields !== null) {
                $body['errors'] = $fields;
            }
            $headers = $error instanceof HttpExceptionInterface ? $error->getHeaders() : [];

            return response()->json($body, $status, $headers);
        });
    })->create();
