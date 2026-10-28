<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Accounts\AccountStatus;
use App\Actions\Accounts\ChangeAccountStatus;
use App\Actions\Accounts\ReadAdminWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAccountStatusRequest;
use App\Http\Requests\AdminAuditListRequest;
use App\Http\Requests\AdminUserListRequest;
use App\Http\Resources\AdminAccountEventResource;
use App\Http\Resources\AdminUserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AdminController extends Controller
{
    public function summary(ReadAdminWorkspace $workspace): JsonResponse
    {
        return ApiResponse::success($workspace->summary());
    }

    public function users(AdminUserListRequest $request, ReadAdminWorkspace $workspace): JsonResponse
    {
        return ApiResponse::page($workspace->users($request->validated()), fn ($user) => new AdminUserResource($user));
    }

    public function events(AdminAuditListRequest $request, ReadAdminWorkspace $workspace): JsonResponse
    {
        return ApiResponse::page($workspace->events($request->validated()), fn ($event) => new AdminAccountEventResource($event));
    }

    public function lock(AdminAccountStatusRequest $request, string $userId, ChangeAccountStatus $action): JsonResponse
    {
        return $this->change($request, $userId, AccountStatus::Locked, $action);
    }

    public function unlock(AdminAccountStatusRequest $request, string $userId, ChangeAccountStatus $action): JsonResponse
    {
        return $this->change($request, $userId, AccountStatus::Active, $action);
    }

    private function change(AdminAccountStatusRequest $request, string $userId, AccountStatus $status, ChangeAccountStatus $action): JsonResponse
    {
        $data = $request->validated();
        $user = $action->handle($request->user(), $userId, $status, $data['expectedVersion'], $data['reason']);

        return ApiResponse::success(new AdminUserResource($user));
    }
}
