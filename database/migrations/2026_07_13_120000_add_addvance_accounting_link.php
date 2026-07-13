<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MODULE_SLUG = 'addvance';

    private const MODULE_URL = 'https://martinsols.addvancesolutions.fr';

    public function up(): void
    {
        if (Schema::hasTable('crm_menu_groups')) {
            DB::table('crm_menu_groups')->updateOrInsert(
                ['menu_key' => 'accounting'],
                [
                    'title' => 'Comptabilité',
                    'active' => true,
                    'sort_order' => 18,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->updateOrInsert(
                ['slug' => self::MODULE_SLUG],
                [
                    'name' => 'Addvance',
                    'description' => 'Accès externe Addvance Solutions',
                    'route_path' => self::MODULE_URL,
                    'active' => true,
                    'sort_order' => 28,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')->updateOrInsert(
                ['item_key' => 'module:'.self::MODULE_SLUG],
                [
                    'group_key' => 'accounting',
                    'icon_key' => 'creditCard',
                    'label' => 'Addvance',
                    'active' => true,
                    'sort_order' => 28,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        $this->grantModuleToExistingUsers();
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_modules')) {
            $moduleId = DB::table('crm_modules')->where('slug', self::MODULE_SLUG)->value('id');

            if ($moduleId && Schema::hasTable('crm_user_modules')) {
                DB::table('crm_user_modules')->where('module_id', $moduleId)->delete();
            }
        }

        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')->where('item_key', 'module:'.self::MODULE_SLUG)->delete();
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->where('slug', self::MODULE_SLUG)->delete();
        }
    }

    private function grantModuleToExistingUsers(): void
    {
        if (! Schema::hasTable('crm_modules') || ! Schema::hasTable('crm_users') || ! Schema::hasTable('crm_user_modules')) {
            return;
        }

        $moduleId = DB::table('crm_modules')->where('slug', self::MODULE_SLUG)->value('id');

        if (! $moduleId) {
            return;
        }

        DB::table('crm_users')
            ->whereIn('role', ['admin', 'responsable', 'user'])
            ->where('active', true)
            ->pluck('id')
            ->each(function ($userId) use ($moduleId): void {
                DB::table('crm_user_modules')->updateOrInsert(
                    [
                        'module_id' => $moduleId,
                        'user_id' => $userId,
                    ],
                    [
                        'created_at' => now(),
                    ],
                );
            });
    }
};
