<?php

namespace App\Actions\Classes;

use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class BuildDashboard
{
    public function forUser(User $actor): array
    {
        abort_unless(in_array($actor->role, ['teacher', 'student'], true), 403);
        $teacher = $actor->role === 'teacher';
        $now = CarbonImmutable::now('UTC');
        $timezone = $actor->timezone ?: 'Asia/Ho_Chi_Minh';
        $today = $now->setTimezone($timezone)->startOfDay();
        $classes = Classroom::where('status', 'active');
        if ($teacher) {
            $classes->where('owner_id', $actor->id);
        } else {
            $classes->whereHas('memberships', fn ($query) => $query->where('student_id', $actor->id)->where('status', 'active'));
        }
        $classIds = (clone $classes)->select('id');
        $sessions = ClassSession::whereIn('class_id', $classIds)->where('status', 'scheduled');
        $upcoming = (clone $sessions)->where('ends_at', '>', $now)->where('starts_at', '<', $now->addDays(7));
        $summary = [
            'activeClasses' => (clone $classes)->count(),
            'todaySessions' => (clone $sessions)->where('starts_at', '>=', $today->utc())->where('starts_at', '<', $today->addDay()->utc())->count(),
            'upcomingSessions' => (clone $upcoming)->count(),
            'unreadCount' => DB::table('inbox_notifications')->where('recipient_id', $actor->id)->whereNull('read_at')->count(),
        ];
        $data = [
            'serverTime' => $now->toISOString(), 'timezone' => $timezone, 'role' => $actor->role,
            'classes' => (clone $classes)->orderByDesc('updated_at')->orderBy('id')->limit(6)->get()->map(fn ($class) => [
                'id' => $class->id, 'name' => $class->name, 'description' => $class->description,
                'timezone' => $class->timezone, 'status' => $class->status, 'version' => $class->version,
                'courseId' => $class->course_id, 'semesterId' => $class->semester_id,
            ])->all(),
            'sessions' => (clone $upcoming)->with('classroom')->orderBy('starts_at')->orderBy('id')->limit(5)->get()->map(fn ($session) => [
                'id' => $session->id, 'classId' => $session->class_id, 'className' => $session->classroom->name,
                'title' => $session->title, 'startsAt' => $session->starts_at->toISOString(), 'endsAt' => $session->ends_at->toISOString(),
                'room' => $session->room, 'status' => $session->status, 'version' => $session->version,
            ])->all(),
        ];
        if ($teacher) {
            $requests = DB::table('class_memberships as m')->join('classes as c', 'c.id', '=', 'm.class_id')
                ->whereIn('m.class_id', $classIds)->where('m.status', 'pending');
            $reviews = DB::table('submissions as s')->join('assignments as a', 'a.id', '=', 's.assignment_id')
                ->join('submission_revisions as r', 'r.id', '=', 's.latest_revision_id')
                ->leftJoin('grades as g', 'g.revision_id', '=', 'r.id')
                ->whereIn('a.class_id', $classIds)->where(fn ($query) => $query->whereNull('g.id')->orWhere('g.state', 'draft'));
            $summary['pendingRequests'] = (clone $requests)->count();
            $summary['pendingReviews'] = (clone $reviews)->count();
            $data['requests'] = (clone $requests)->select('c.id as classId', 'c.name as className')->selectRaw('COUNT(*) as pendingCount')
                ->groupBy('c.id', 'c.name')->orderBy('c.name')->orderBy('c.id')->limit(5)->get()->all();
            $data['reviews'] = (clone $reviews)->select('s.id as submissionId', 'a.id as assignmentId', 'a.class_id as classId', 'a.title')
                ->orderBy('r.submitted_at')->orderBy('s.id')->limit(5)->get()->all();
        } else {
            $assignments = DB::table('assignments as a')->join('classes as c', 'c.id', '=', 'a.class_id')
                ->whereIn('a.class_id', $classIds)->where('a.state', 'published')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('submissions as s')->whereColumn('s.assignment_id', 'a.id')->where('s.student_id', $actor->id));
            $grades = DB::table('submissions as s')->join('assignments as a', 'a.id', '=', 's.assignment_id')
                ->join('grade_publications as p', 'p.id', '=', 's.published_grade_publication_id')
                ->whereIn('a.class_id', $classIds)->where('s.student_id', $actor->id);
            $summary['pendingAssignments'] = (clone $assignments)->count();
            $summary['publishedGrades'] = (clone $grades)->count();
            $data['assignments'] = (clone $assignments)->select('a.id', 'a.class_id as classId', 'a.title', 'c.name as className', 'a.due_at', 'a.allow_late')
                ->orderByRaw('a.due_at IS NULL')->orderBy('a.due_at')->orderBy('a.id')->limit(5)->get()->map(fn ($assignment) => [
                    'id' => $assignment->id, 'classId' => $assignment->classId, 'className' => $assignment->className, 'title' => $assignment->title,
                    'dueAt' => $assignment->due_at ? CarbonImmutable::parse($assignment->due_at, 'UTC')->toISOString() : null,
                    'allowLate' => (bool) $assignment->allow_late,
                ])->all();
            $data['grades'] = (clone $grades)->select('s.id as submissionId', 'a.title', 'p.score', 'p.published_at')
                ->orderByDesc('p.published_at')->orderBy('p.id')->limit(5)->get()->map(fn ($grade) => [
                    'submissionId' => $grade->submissionId, 'title' => $grade->title, 'score' => (float) $grade->score,
                    'publishedAt' => CarbonImmutable::parse($grade->published_at, 'UTC')->toISOString(),
                ])->all();
        }

        return [...$data, 'summary' => $summary];
    }
}
