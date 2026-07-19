<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_users')
            || ! Schema::hasTable('crm_modules')
            || ! Schema::hasTable('crm_permissions')
            || ! Schema::hasTable('crm_user_modules')
            || ! Schema::hasTable('crm_user_permissions')) {
            return;
        }

        $moduleId = (int) DB::table('crm_modules')->where('slug', 'tournees-representants')->value('id');
        $permissionIds = DB::table('crm_permissions')
            ->whereIn('name', [
                'sales_tours.view',
                'sales_tours.create',
                'sales_tours.report',
                'sales_tours.manage',
            ])
            ->pluck('id', 'name')
            ->map(fn ($id): int => (int) $id);

        if ($moduleId <= 0 || $permissionIds->count() < 4) {
            return;
        }

        $now = now();
        $users = DB::table('crm_users')
            ->where('active', true)
            ->where('role', '<>', 'blocked')
            ->get(['id', 'role']);

        foreach ($users as $user) {
            $userId = (int) $user->id;

            DB::table('crm_user_modules')->updateOrInsert(
                ['module_id' => $moduleId, 'user_id' => $userId],
                ['created_at' => $now],
            );

            foreach (['sales_tours.view', 'sales_tours.create', 'sales_tours.report'] as $permission) {
                DB::table('crm_user_permissions')->updateOrInsert(
                    ['permission_id' => $permissionIds[$permission], 'user_id' => $userId],
                    ['created_at' => $now],
                );
            }

            if (in_array($user->role, ['admin', 'responsable'], true)) {
                DB::table('crm_user_permissions')->updateOrInsert(
                    ['permission_id' => $permissionIds['sales_tours.manage'], 'user_id' => $userId],
                    ['created_at' => $now],
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_modules') || ! Schema::hasTable('crm_permissions')) {
            return;
        }

        $moduleId = (int) DB::table('crm_modules')->where('slug', 'tournees-representants')->value('id');
        $permissionIds = DB::table('crm_permissions')
            ->whereIn('name', [
                'sales_tours.view',
                'sales_tours.create',
                'sales_tours.report',
                'sales_tours.manage',
            ])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($moduleId > 0 && Schema::hasTable('crm_user_modules')) {
            DB::table('crm_user_modules')->where('module_id', $moduleId)->delete();
        }

        if ($permissionIds !== [] && Schema::hasTable('crm_user_permissions')) {
            DB::table('crm_user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }
    }
};
