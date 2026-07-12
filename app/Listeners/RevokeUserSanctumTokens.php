<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;

class RevokeUserSanctumTokens
{
    public function handle(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $event->user->tokens()->delete();
        }
    }
}
