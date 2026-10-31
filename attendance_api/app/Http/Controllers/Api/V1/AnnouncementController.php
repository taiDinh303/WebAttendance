<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Announcements\ManageAnnouncements;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnnouncementCommentRequest;
use App\Http\Requests\AnnouncementPageRequest;
use App\Http\Requests\AnnouncementRequest;
use App\Http\Resources\AnnouncementCommentResource;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\Classroom;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class AnnouncementController extends Controller
{
    public function index(AnnouncementPageRequest $request, Classroom $classroom, ManageAnnouncements $action): JsonResponse
    {
        Gate::authorize('view', $classroom);
        $query = Announcement::where('class_id', $classroom->id);
        if ($request->user()->role === 'student') {
            $query->where('state', 'published');
        }
        $data = $request->validated();
        if (! empty($data['q'])) {
            $query->where('title', 'like', '%'.addcslashes($data['q'], '%_\\').'%');
        }
        if (array_key_exists('state', $data) && $request->user()->role === 'teacher') {
            $query->where('state', $data['state']);
        }
        if (array_key_exists('pinned', $data)) {
            $query->where('pinned', $data['pinned']);
        }

        return ApiResponse::page(
            $query->orderByDesc('pinned')->orderByDesc('created_at')->orderBy('id')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1),
            fn ($a) => new AnnouncementResource($a),
        );
    }

    public function store(AnnouncementRequest $request, Classroom $classroom, ManageAnnouncements $action): JsonResponse
    {
        $announcement = $action->save($request->user(), $classroom, $request->validated());

        return ApiResponse::success(new AnnouncementResource($announcement), 201)->header('Location', '/api/v1/classes/'.$classroom->id.'/announcements/'.$announcement->id);
    }

    public function show(Request $request, Classroom $classroom, string $announcementId): JsonResponse
    {
        Gate::authorize('view', $classroom);
        $query = Announcement::where('class_id', $classroom->id)->whereKey($announcementId);
        if ($request->user()->role === 'student') {
            $query->where('state', 'published');
        }
        $announcement = $query->firstOrFail();
        $commentQuery = AnnouncementComment::where('announcement_id', $announcement->id);
        if ($request->user()->role === 'student') {
            // Students see visible live comments plus their OWN soft-deleted ones (marked with deletedAt),
            // so they learn when a teacher removed a comment. Hidden comments stay invisible to everyone.
            $commentQuery->where(function ($q) use ($request) {
                $q->where('hidden', false)->whereNull('deleted_at');
                $q->orWhere(fn ($sub) => $sub->where('author_id', $request->user()->id)->whereNotNull('deleted_at'));
            });
        }
        $comments = $commentQuery->withTrashed()->orderBy('created_at')->orderBy('id')->get();

        return ApiResponse::success([
            'announcement' => new AnnouncementResource($announcement),
            'comments' => $comments->map(fn ($c) => new AnnouncementCommentResource($c)),
        ]);
    }

    public function update(AnnouncementRequest $request, Classroom $classroom, string $announcementId, ManageAnnouncements $action): JsonResponse
    {
        $announcement = $action->save($request->user(), $classroom, $request->validated(), $announcementId);

        return ApiResponse::success(new AnnouncementResource($announcement));
    }

    public function publish(Request $request, Classroom $classroom, string $announcementId, ManageAnnouncements $action): JsonResponse
    {
        $announcement = $action->publish($request->user(), $classroom, $announcementId, (int) $request->input('expectedVersion', 1));

        return ApiResponse::success(new AnnouncementResource($announcement));
    }

    public function storeComment(AnnouncementCommentRequest $request, Classroom $classroom, string $announcementId, ManageAnnouncements $action): JsonResponse
    {
        $data = $request->validated();
        $comment = $action->addComment($request->user(), $classroom, $announcementId, $data['body'], $data['clientMessageId'] ?? null);

        return ApiResponse::success(new AnnouncementCommentResource($comment), 201);
    }

    public function hideComment(Request $request, Classroom $classroom, string $commentId, ManageAnnouncements $action): Response
    {
        $action->moderateComment($request->user(), $classroom, $commentId, true);

        return response()->noContent();
    }

    public function deleteComment(Request $request, Classroom $classroom, string $commentId, ManageAnnouncements $action): Response
    {
        $action->deleteComment($request->user(), $classroom, $commentId);

        return response()->noContent();
    }

    public function destroy(Request $request, Classroom $classroom, string $announcementId, ManageAnnouncements $action): JsonResponse
    {
        $announcement = $action->archive($request->user(), $classroom, $announcementId, (int) $request->input('expectedVersion', 1));

        return ApiResponse::success(new AnnouncementResource($announcement));
    }
}
