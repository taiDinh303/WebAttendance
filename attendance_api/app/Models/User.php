<?php

namespace App\Models;

use App\Notifications\ResetAccountPassword;
use App\Notifications\VerifyAccountEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['display_name', 'email', 'password', 'phone', 'locale', 'timezone'])]
#[Hidden(['password', 'remember_token', 'auth_version'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable;

    protected $attributes = ['account_status' => 'active', 'version' => 1, 'auth_version' => 1, 'locale' => 'vi', 'timezone' => 'Asia/Ho_Chi_Minh'];

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyAccountEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetAccountPassword($token));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'version' => 'integer',
            'auth_version' => 'integer',
        ];
    }
}
