<?php

namespace App\Actions\Classes;

use App\Exceptions\ApiException;
use App\Models\Classroom;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class SaveSemester
{
    public function handle(User $actor, array $data, ?string $id = null): Semester
    {
        return DB::transaction(function () use ($actor, $data, $id) {
            $semester = $id ? Semester::whereKey($id)->lockForUpdate()->firstOrFail() : new Semester;
            Gate::forUser($actor)->authorize($id ? 'update' : 'create', $id ? $semester : Semester::class);
            if ($id && $semester->version !== (int) $data['expectedVersion']) {
                throw new ApiException('VERSION_CONFLICT', 'Semester changed. Reload before saving.');
            }
            $semester->fill(['name' => $data['name'], 'start_date' => $data['startDate'], 'end_date' => $data['endDate'], 'is_active' => $data['isActive'] ?? ($semester->exists ? $semester->is_active : true)]);
            $semester->owner_id = $actor->id;
            if ($id) {
                $semester->version++;
            }
            $semester->save();

            return $semester;
        });
    }

    public function delete(User $actor, string $id, int $version): void
    {
        DB::transaction(function () use ($actor, $id, $version) {
            $semester = Semester::whereKey($id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $semester);
            if ($semester->version !== $version) {
                throw new ApiException('VERSION_CONFLICT', 'Semester changed.');
            }
            if (Classroom::where('semester_id', $id)->exists()) {
                throw new ApiException('RESOURCE_IN_USE', 'This semester is used by a class.');
            }
            $semester->delete();
        });
    }
}
