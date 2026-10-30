<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'displayName' => $this->display_name, 'email' => $this->email,
            'role' => $this->role, 'accountStatus' => $this->account_status,
            'emailVerified' => $this->hasVerifiedEmail(), 'phone' => $this->phone,
            'avatarKey' => $this->avatar_key, 'gender' => $this->gender, 'birthday' => $this->birthday,
            'locale' => $this->locale, 'timezone' => $this->timezone, 'version' => $this->version];
    }
}
