<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Semester;
use App\Policies\TeacherCatalogPolicy;
use App\Support\ApiDocumentation;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap policies, rate limits and development API documentation.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
        if (class_exists(Scramble::class)) {
            Scramble::afterOpenApiGenerated(fn (OpenApi $api) => ApiDocumentation::transform($api));
        }
        Gate::policy(Course::class, TeacherCatalogPolicy::class);
        Gate::policy(Semester::class, TeacherCatalogPolicy::class);
        ResetPassword::createUrlUsing(fn ($user, string $token) => rtrim(config('app.frontend_url'), '/').'/reset-password?'.http_build_query(['token' => $token, 'email' => $user->email]));
        foreach (['login' => 5, 'register' => 5, 'recovery' => 5, 'verification' => 2, 'join' => 10, 'check-in' => 30, 'upload' => 10, 'submit' => 10] as $name => $attempts) {
            RateLimiter::for($name, function (Request $request) use ($attempts, $name) {
                $perActor = in_array($name, ['check-in', 'upload', 'submit'], true);
                $identity = $perActor ? (string) $request->user()?->id
                    : mb_strtolower((string) $request->input('email', $request->user()?->id ?? '')).'|'.$request->ip();

                return [
                    Limit::perMinute($attempts)->by($name.'|'.hash('sha256', $identity)),
                    Limit::perMinute($perActor ? 600 : 30)->by($name.'|ip|'.$request->ip()),
                ];
            });
        }
    }
}
