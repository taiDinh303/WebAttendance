<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

final class ResetAccountPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct(string $token)
    {
        parent::__construct($token);
        $this->afterCommit();
    }
}
