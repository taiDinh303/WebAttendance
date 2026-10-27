<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Enrollment\ManageEnrollment;
use App\Http\Controllers\Controller;
use App\Http\Requests\JoinRequest;
use App\Http\Requests\PageRequest;
use App\Http\Requests\VersionRequest;
use App\Http\Resources\MembershipResource;
use App\Models\ClassMembership;
use App\Models\Classroom;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class EnrollmentController extends Controller
{
    public function join(JoinRequest $request, ManageEnrollment $action): JsonResponse
    {
        [$member, $created] = $action->requestJoin($request->user(), $request->validated('inviteCode'));

        return ApiResponse::success(new MembershipResource($member), $created ? 201 : 200);
    }

    public function mine(PageRequest $request): JsonResponse
    {
        abort_unless($request->user()->role === 'student', 403);
        $query = ClassMembership::where('student_id', $request->user()->id)->with('classroom');

        return $this->listing($request, $query);
    }

    public function requests(PageRequest $request, Classroom $classroom): JsonResponse
    {
        Gate::authorize('update', $classroom);
        $query = $classroom->memberships()->with('student')->where('status', $request->validated('status', 'pending'));

        return $this->listing($request, $query, false);
    }

    public function members(PageRequest $request, Classroom $classroom): JsonResponse
    {
        // Student roster access is deliberately unavailable until explicitly specified.
        Gate::authorize('update', $classroom);
        $query = $classroom->memberships()->with('student')->where('status', $request->validated('status', 'active'));

        return $this->listing($request, $query, false);
    }

    public function ownerChange(VersionRequest $request, Classroom $classroom, string $membershipId, ManageEnrollment $action): JsonResponse
    {
        $member = $action->ownerTransition($request->user(), $classroom, $membershipId, $request->route()->defaults['operation'], $request->validated());

        return ApiResponse::success(new MembershipResource($member));
    }

    public function remove(VersionRequest $request, Classroom $classroom, string $studentId, ManageEnrollment $action): JsonResponse
    {
        Gate::authorize('update', $classroom);
        $member = $classroom->memberships()->where('student_id', $studentId)->firstOrFail();

        return ApiResponse::success(new MembershipResource($action->ownerTransition($request->user(), $classroom, $member->id, 'remove', $request->validated())));
    }

    public function leave(VersionRequest $request, Classroom $classroom, ManageEnrollment $action): JsonResponse
    {
        $member = $classroom->memberships()->where('student_id', $request->user()->id)->firstOrFail();

        return ApiResponse::success(new MembershipResource($action->selfTransition($request->user(), $member->id, 'leave', $request->integer('expectedVersion'))));
    }

    public function cancel(VersionRequest $request, string $membershipId, ManageEnrollment $action): JsonResponse
    {
        return ApiResponse::success(new MembershipResource($action->selfTransition($request->user(), $membershipId, 'cancel', $request->integer('expectedVersion'))));
    }

    private function listing(PageRequest $request, Builder|HasMany $query, bool $filterStatus = true): JsonResponse
    {
        $data = $request->validated();
        if ($filterStatus && isset($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (! empty($data['q'])) {
            $query->whereHas('student', fn ($q) => $q->where('display_name', 'like', '%'.addcslashes($data['q'], '%_\\').'%'));
        }

        return ApiResponse::page($query->orderByDesc('requested_at')->orderBy('id')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($member) => new MembershipResource($member));
    }
}
