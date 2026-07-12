<?php

namespace App\Services\Crm;

use App\Models\CrmUser;
use Illuminate\Support\Facades\DB;

class CrmActivityLogger
{
    public function log(CrmUser $actor, string $action, string $details = ''): void
    {
        DB::table('crm_logs')->insert([
            'user_id' => $actor->id,
            'user_name' => $actor->name,
            'action' => $action,
            'details' => $details,
            'created_at' => now(),
            'ip' => request()->ip() ?? '',
        ]);
    }
}
