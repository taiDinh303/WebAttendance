<?php

namespace App\Actions\Accounts;

enum AccountStatus: string
{
    case Active = 'active';
    case Locked = 'locked';
}
