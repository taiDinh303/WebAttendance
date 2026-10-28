<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'displayName' => $this->display_name, 'email' => $this->email, 'role' => $this->role,
            'accountStatus' => $this->account_status, 'emailVerified' => $this->hasVerifiedEmail(),
            'version' => $this->version, 'createdAt' => $this->created_at->toISOString(),
            'isSelf' => $request->user()->id === $this->id,
        ];
    }
}
