<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    public function update(ProfileRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($user->version !== (int) $data['expectedVersion']) {
                throw new ApiException('VERSION_CONFLICT', 'Profile changed. Reload before saving.');
            }
            foreach (['displayName' => 'display_name', 'phone' => 'phone', 'locale' => 'locale', 'timezone' => 'timezone'] as $field => $column) {
                if (array_key_exists($field, $data)) {
                    $user->{$column} = $data[$field];
                }
            }
            $user->version++;
            $user->save();

            return $user;
        });

        return ApiResponse::success(new UserResource($user));
    }
}
