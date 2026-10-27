<?php

namespace App\Actions\Classes;

use App\Exceptions\ApiException;
use App\Models\AttendanceWindow;
use App\Models\ClassInvite;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ManageClassroom
{
    public function create(User $actor, array $data): array
    {
        Gate::forUser($actor)->authorize('create', Classroom::class);

        return DB::transaction(function () use ($actor, $data) {
            $class = new Classroom($this->fields($data));
            $class->owner_id = $actor->id;
            $class->save();

            return [$class, $this->issueInvite($class, null)];
        });
    }

    public function mutate(User $actor, string $id, array $data, string $operation): array
    {
        return DB::transaction(function () use ($actor, $id, $data, $operation) {
            $class = Classroom::whereKey($id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $class);
            if ($class->version !== (int) $data['expectedVersion']) {
                throw new ApiException('VERSION_CONFLICT', 'Class changed. Reload before saving.');
            }
            if ($class->status !== 'active') {
                throw new ApiException('STATE_CONFLICT', 'Archived classes cannot be changed.');
            }
            $code = null;
            if ($operation === 'update') {
                $class->fill($this->fields($data));
            } elseif ($operation === 'archive') {
                if (AttendanceWindow::whereIn('session_id', ClassSession::select('id')->where('class_id', $class->id))->where('state', 'open')->where('closes_at', '>', now())->exists()) {
                    throw new ApiException('STATE_CONFLICT', 'Close active attendance windows before archiving the class.');
                }
                $class->status = 'archived';
                $class->archived_at = now();
                ClassInvite::where('class_id', $class->id)->update(['enabled' => false]);
            } elseif ($operation === 'rotate') {
                $code = $this->issueInvite($class, $data['expiresAt'] ?? null);
            } elseif ($operation === 'disable') {
                ClassInvite::where('class_id', $class->id)->update(['enabled' => false]);
            }
            $class->version++;
            $class->save();

            return [$class, $code];
        });
    }

    private function fields(array $data): array
    {
        $out = [];
        foreach (['name' => 'name', 'description' => 'description', 'timezone' => 'timezone', 'semesterId' => 'semester_id', 'courseId' => 'course_id'] as $field => $column) {
            if (array_key_exists($field, $data)) {
                $out[$column] = $data[$field];
            }
        }

        return $out;
    }

    private function issueInvite(Classroom $class, ?string $expiresAt): string
    {
        $code = strtoupper(bin2hex(random_bytes(8)));
        ClassInvite::updateOrCreate(['class_id' => $class->id], ['code_hash' => hash('sha256', $code), 'enabled' => true, 'expires_at' => $expiresAt]);

        return $code;
    }
}
