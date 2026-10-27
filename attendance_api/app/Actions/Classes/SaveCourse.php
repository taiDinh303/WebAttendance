<?php

namespace App\Actions\Classes;

use App\Exceptions\ApiException;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class SaveCourse
{
    public function handle(User $actor, array $data, ?string $id = null): Course
    {
        return DB::transaction(function () use ($actor, $data, $id) {
            $course = $id ? Course::whereKey($id)->lockForUpdate()->firstOrFail() : new Course;
            Gate::forUser($actor)->authorize($id ? 'update' : 'create', $id ? $course : Course::class);
            if ($id && (int) $course->version !== (int) $data['expectedVersion']) {
                throw new ApiException('VERSION_CONFLICT', 'Course changed. Reload before saving.');
            }
            $course->name = $data['name'];
            if (array_key_exists('code', $data)) {
                $course->code = $data['code'];
            }
            $course->owner_id = $actor->id;
            if ($id) {
                $course->version++;
            }
            $course->save();

            return $course;
        });
    }

    public function delete(User $actor, string $id, int $version): void
    {
        DB::transaction(function () use ($actor, $id, $version) {
            $course = Course::whereKey($id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $course);
            if ((int) $course->version !== $version) {
                throw new ApiException('VERSION_CONFLICT', 'Course changed.');
            }
            if (Classroom::where('course_id', $id)->exists()) {
                throw new ApiException('RESOURCE_IN_USE', 'This course is used by a class.');
            }
            $course->delete();
        });
    }
}
