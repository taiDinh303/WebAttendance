<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadRequest;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\PrivateFileValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FileController extends Controller
{
    public function upload(UploadRequest $request, Classroom $classroom, PrivateFileValidation $validation): JsonResponse
    {
        $data = $request->validated();
        $actor = $request->user();
        $assignment = Assignment::where('class_id', $classroom->id)->whereKey($data['assignmentId'])->firstOrFail();
        $this->authorizeUpload($actor, $classroom, $assignment, $data['purpose']);
        $upload = $request->file('file');
        $safe = $validation->assess($upload);
        $name = mb_substr(basename(str_replace('\\', '/', $upload->getClientOriginalName())), 0, 200);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '_', mb_convert_encoding($name, 'UTF-8', 'UTF-8'));
        $path = Storage::disk('local')->putFileAs('class-files/'.$classroom->id, $upload, (string) Str::ulid().'.'.$safe['extension']);
        if (! $path) {
            throw new ApiException('STORAGE_UNAVAILABLE', 'The file could not be stored.', 503);
        }
        try {
            $file = DB::transaction(function () use ($actor, $classroom, $assignment, $data, $safe, $name, $path) {
                $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
                $a = Assignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
                $this->authorizeUpload($actor, $class, $a, $data['purpose']);

                return StoredFile::create(['class_id' => $class->id, 'uploaded_by' => $actor->id, 'assignment_id' => $a->id, 'purpose' => $data['purpose'],
                    'storage_key' => $path, 'original_name' => $name, 'mime_type' => $safe['mimeType'], 'size_bytes' => $safe['sizeBytes'], 'sha256' => $safe['sha256'], 'scan_state' => $safe['scanState']]);
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        return ApiResponse::success(['id' => $file->id, 'originalName' => $file->original_name, 'mimeType' => $file->mime_type,
            'sizeBytes' => (int) $file->size_bytes, 'scanState' => $file->scan_state, 'state' => $file->state], 201);
    }

    public function download(Request $request, string $fileId, PrivateFileValidation $validation): StreamedResponse
    {
        $file = StoredFile::whereKey($fileId)->whereIn('state', ['staged', 'attached'])->firstOrFail();
        $class = Classroom::findOrFail($file->class_id);
        Gate::authorize('view', $class);
        $actor = $request->user();
        if ($actor->role === 'student') {
            if ($file->purpose === 'submission') {
                abort_unless($file->uploaded_by === $actor->id, 404);
            } else {
                $visible = DB::table('assignment_files as l')->join('assignments as a', 'a.id', '=', 'l.assignment_id')->where('l.file_id', $file->id)->whereIn('a.state', ['published', 'closed'])->exists();
                abort_unless($visible, 404);
            }
        }
        $validation->requireApproved($file->scan_state);
        abort_unless(Storage::disk('local')->exists($file->storage_key), 404);

        return Storage::disk('local')->download($file->storage_key, $file->original_name, ['Content-Type' => $file->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store, private']);
    }

    public function destroy(Request $request, string $fileId): Response
    {
        $path = DB::transaction(function () use ($request, $fileId) {
            $candidate = StoredFile::findOrFail($fileId);
            $class = Classroom::whereKey($candidate->class_id)->lockForUpdate()->firstOrFail();
            Gate::authorize('view', $class);
            $file = StoredFile::whereKey($fileId)->lockForUpdate()->firstOrFail();
            abort_unless($file->uploaded_by === $request->user()->id, 404);
            if ($file->state !== 'staged') {
                throw new ApiException('FILE_IN_USE', 'Attached files are immutable.');
            }
            $file->state = 'deleted';
            $file->save();

            return $file->storage_key;
        });
        Storage::disk('local')->delete($path);

        return response()->noContent();
    }

    private function authorizeUpload(User $actor, Classroom $class, Assignment $a, string $purpose): void
    {
        if ($class->status !== 'active') {
            throw new ApiException('STATE_CONFLICT', 'The class is archived.');
        }
        if ($purpose === 'assignment_material') {
            Gate::forUser($actor)->authorize('update', $class);
            if ($a->state === 'closed') {
                throw new ApiException('STATE_CONFLICT', 'The assignment is closed.');
            }
        } else {
            abort_unless($actor->role === 'student', 403);
            Gate::forUser($actor)->authorize('view', $class);
            if ($a->state !== 'published') {
                throw new ApiException('STATE_CONFLICT', 'The assignment is not accepting uploads.');
            }
            if ($a->due_at && now()->gt($a->due_at) && ! $a->allow_late) {
                throw new ApiException('DEADLINE_PASSED', 'The deadline has passed.', 422);
            }
        }
    }
}
