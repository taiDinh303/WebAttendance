<?php

use App\Exceptions\ApiException;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    return response()->json(['name' => 'AttendanceWeb API', 'version' => 'v1']);
});

Route::get('/health/live', fn () => ApiResponse::success(['status' => 'alive']))->withoutMiddleware('web');
Route::get('/health/ready', function () {
    try {
        DB::select('SELECT 1');
        if (! Schema::hasTable('users')) {
            throw new RuntimeException('Schema not initialized');
        }
    } catch (Throwable) {
        throw new ApiException('SERVICE_UNAVAILABLE', 'Database is not ready.', 503);
    }

    return ApiResponse::success(['status' => 'ready']);
})->withoutMiddleware('web');
