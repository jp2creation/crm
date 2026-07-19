<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
                ['slug' => 'remise-cheques'],
                [
                    'name' => 'Remise de chèques',
                    'description' => 'Remises de chèques, photos, contrôle des montants et impression PDF',
                    'route_path' => '/remise-cheques',
                    'active' => true,
                    'sort_order' => 27,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')
                ->where('group_key', 'check_remittances')
                ->update([
                    'group_key' => 'accounting',
                    'updated_at' => now(),
                ]);

            DB::table('crm_menu_items')->updateOrInsert(
                ['item_key' => 'module:remise-cheques'],
                [
                    'group_key' => 'accounting',
                    'icon_key' => 'creditCard',
                    'label' => 'Remise de chèques',
                    'active' => true,
                    'sort_order' => 27,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_menu_groups')) {
            DB::table('crm_menu_groups')
                ->where('menu_key', 'check_remittances')
                ->delete();
        }

        if (Schema::hasTable('crm_permissions')) {
            foreach ([
                ['check_remittances.view', 'Voir les remises de chèques', 'Remise de chèques', 149],
                ['check_remittances.manage', 'Gérer les remises de chèques', 'Remise de chèques', 150],
            ] as [$name, $label, $group, $sortOrder]) {
                DB::table('crm_permissions')->updateOrInsert(
                    ['name' => $name],
                    [
                        'label' => $label,
                        'group_label' => $group,
                        'sort_order' => $sortOrder,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_menu_groups')) {
            DB::table('crm_menu_groups')->updateOrInsert(
                ['menu_key' => 'check_remittances'],
                [
                    'title' => 'REMISE DE CHÈQUES',
                    'active' => true,
                    'sort_order' => 19,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')
                ->where('item_key', 'module:remise-cheques')
                ->update([
                    'group_key' => 'check_remittances',
                    'label' => 'Tableau de bord',
                    'sort_order' => 10,
                    'updated_at' => now(),
                ]);
        }
    }
};
