<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $crmUserId = $this->crmUserId();

        if ($crmUserId <= 0) {
            return;
        }

        $moduleId = (int) DB::table('crm_modules')->where('slug', 'conges')->value('id');
        if ($moduleId > 0) {
            DB::table('crm_user_modules')->updateOrInsert(
                ['module_id' => $moduleId, 'user_id' => $crmUserId],
                ['created_at' => $now],
            );
        }

        $permissionIds = DB::table('crm_permissions')
            ->whereIn('name', ['conges.view', 'conges.manage'])
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('crm_user_permissions')->updateOrInsert(
                ['permission_id' => (int) $permissionId, 'user_id' => $crmUserId],
                ['created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        $crmUserId = $this->crmUserId();

        if ($crmUserId <= 0) {
            return;
        }

        $managePermissionId = (int) DB::table('crm_permissions')->where('name', 'conges.manage')->value('id');
        if ($managePermissionId > 0) {
            DB::table('crm_user_permissions')
                ->where('user_id', $crmUserId)
                ->where('permission_id', $managePermissionId)
                ->delete();
        }
    }

    private function crmUserId(): int
    {
        $accountId = DB::table('users')->where('email', 'peinture.pau@martinsols.com')->value('id');

        if ($accountId) {
            $crmUserId = DB::table('crm_users')->where('user_id', $accountId)->value('id');
            if ($crmUserId) {
                return (int) $crmUserId;
            }
        }

        $crmUserId = DB::table('crm_users')->where('email', 'peinture.pau@martinsols.com')->value('id');
        if ($crmUserId) {
            return (int) $crmUserId;
        }

        return (int) (DB::table('crm_users')
            ->whereIn('name', ['Jean-Philippe', 'J-Philippe', 'JEAN-PHILIPPE'])
            ->value('id') ?: 0);
    }
};
