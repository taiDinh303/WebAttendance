<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PreferenceRequest;
use App\Models\Classroom;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class PreferenceController extends Controller
{
    private function scope(Request $request, string $key): Builder
    {
        abort_unless(in_array($key, ['theme', 'sidebar-collapsed', 'sidebar-hidden'], true) || preg_match('/^banner-[0-9A-HJKMNP-TV-Z]{26}$/iD', $key), 404);
        if (str_starts_with($key, 'banner-')) {
            Gate::authorize('view', Classroom::findOrFail(substr($key, 7)));
        }

        return DB::table('account_preferences')->where('user_id', $request->user()->id)->where('key', $key);
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $row = $this->scope($request, $key)->first();

        return ApiResponse::success(['value' => $row ? json_decode($row->value, true) : null, 'version' => $row ? (int) $row->version : 0]);
    }

    public function update(PreferenceRequest $request, string $key): JsonResponse
    {
        $query = $this->scope($request, $key);
        $data = $request->validated();
        if (str_starts_with($key, 'banner-')) {
            $data['value']['image'] = $data['value']['image'] ?? '';
        }
        $result = DB::transaction(function () use ($request, $key, $query, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $row = $query->first();
            $version = $row ? (int) $row->version : 0;
            if ($version !== (int) $data['expectedVersion']) {
                throw new ApiException('VERSION_CONFLICT', 'Preference changed. Reload before saving.');
            }$value = json_encode($data['value'], JSON_THROW_ON_ERROR);
            DB::table('account_preferences')->upsert([['user_id' => $request->user()->id, 'key' => $key, 'value' => $value, 'version' => $version + 1]], ['user_id', 'key'], ['value', 'version']);

            return ['value' => $data['value'], 'version' => $version + 1];
        });

        return ApiResponse::success($result);
    }
}
