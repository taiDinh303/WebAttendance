<?php

namespace App\Http\Requests;

final class UploadRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['file' => ['required', 'file', 'max:20480'], 'purpose' => ['required', 'string', 'in:assignment_material,submission'],
            'assignmentId' => ['required', 'ulid'], 'uploadedBy' => ['prohibited'], 'scanState' => ['prohibited']];
    }
}
