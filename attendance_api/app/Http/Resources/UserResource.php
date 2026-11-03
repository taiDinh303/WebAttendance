<?php

namespace App\Http\Resources;

use App\Actions\Accounts\PortfolioDemo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['portfolioDemo' => config('demo.portfolio_enabled') && app(PortfolioDemo::class)->contains($this->resource), 'studentCode' => $this->student_code, 'id' => $this->id, 'displayName' => $this->display_name, 'email' => $this->email,
            'role' => $this->role, 'accountStatus' => $this->account_status,
            'emailVerified' => $this->hasVerifiedEmail(), 'phone' => $this->phone,
            'avatarKey' => $this->avatar_key, 'gender' => $this->gender, 'birthday' => $this->birthday,
            'locale' => $this->locale, 'timezone' => $this->timezone, 'version' => $this->version];
    }
}
