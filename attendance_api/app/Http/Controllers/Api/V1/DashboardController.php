<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Classes\BuildDashboard;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function show(Request $request, BuildDashboard $dashboard): JsonResponse
    {
        return ApiResponse::success($dashboard->forUser($request->user()));
    }
}
