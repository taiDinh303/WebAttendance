<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class NotificationController extends Controller
{
    public function index(PageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $query = DB::table('inbox_notifications')->where('recipient_id', $request->user()->id);
        if (array_key_exists('read', $data)) {
            $data['read'] ? $query->whereNotNull('read_at') : $query->whereNull('read_at');
        }

        return ApiResponse::page($query->orderByDesc('created_at')->orderBy('id')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($row) => [
            'id' => $row->id, 'type' => $row->type, 'resourceId' => $row->resource_id,
            'data' => json_decode($row->payload, true),
            'readAt' => $row->read_at ? CarbonImmutable::parse($row->read_at, 'UTC')->toISOString() : null,
            'createdAt' => CarbonImmutable::parse($row->created_at, 'UTC')->toISOString(),
        ]);
    }

    public function count(Request $request): JsonResponse
    {
        return ApiResponse::success(['unreadCount' => DB::table('inbox_notifications')->where('recipient_id', $request->user()->id)->whereNull('read_at')->count()]);
    }

    public function read(Request $request, string $notificationId): Response
    {
        $row = DB::table('inbox_notifications')->where('recipient_id', $request->user()->id)->where('id', $notificationId)->first();
        abort_unless($row, 404);
        DB::table('inbox_notifications')->where('id', $row->id)->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->noContent();
    }
}
