<?php

namespace App\Observers;

use App\Models\User;

class UserTokenObserver
{
    public function updated(User $user): void
    {
        if ($user->wasChanged('password')) {
            $user->tokens()->delete();
        }
    }
}
