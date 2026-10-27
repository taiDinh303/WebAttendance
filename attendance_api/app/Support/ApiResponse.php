<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json(['code' => 'SUCCESS', 'message' => 'OK', 'data' => $data, 'traceId' => request()->attributes->get('traceId')], $status);
    }

    public static function page(LengthAwarePaginator $page, callable $map): JsonResponse
    {
        return self::success([
            'items' => $page->getCollection()->map($map)->values(),
            'pageNumber' => $page->currentPage(), 'pageSize' => $page->perPage(),
            'totalItems' => $page->total(), 'totalPages' => $page->total() === 0 ? 0 : $page->lastPage(),
            'hasNextPage' => $page->hasMorePages(), 'hasPreviousPage' => $page->currentPage() > 1,
        ]);
    }
}
