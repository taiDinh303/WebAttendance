<?php

namespace App\Http\Middleware;

use App\Actions\Accounts\PortfolioDemo;
use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class ProtectPortfolioDemo
{
    public function handle(Request $request, Closure $next): Response
    {
        $demo = app(PortfolioDemo::class);
        if (! config('demo.portfolio_enabled') || ! $request->user() || ! $demo->contains($request->user()) || $request->isMethodSafe()) {
            return $next($request);
        }

        return DB::transaction(function () use ($request, $next, $demo) {
            DB::table('demo_datasets')->where('name', PortfolioDemo::DATASET)->lockForUpdate()->first();
            if ($request->is('api/v1/me/change-password', 'api/v1/me/profile', 'api/v1/me/google', 'api/v1/classes/*/files')) {
                throw new ApiException('DEMO_RESTRICTED', 'Tài khoản demo giữ nguyên hồ sơ và mật khẩu, không tải tệp lên. Bạn vẫn có thể thử nộp bài bằng ghi chú.', 403);
            }
            if (! $request->is('api/v1/auth/logout') && $demo->overLimit($demo->manifest())) {
                throw new ApiException('DEMO_LIMIT', 'Bộ demo đã đạt giới hạn. Vui lòng đăng xuất và đăng nhập lại để làm mới dữ liệu demo.', 429);
            }

            return $next($request);
        });
    }
}
