<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;

class RevokeUserSanctumTokens
{
    public function handle(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $event->user->tokens()->delete();

            DB::table('crm_mobile_refresh_tokens')
                ->where('user_id', $event->user->id)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                    'revoked_reason' => 'password_reset',
                    'updated_at' => now(),
                ]);
        }
    }
}
