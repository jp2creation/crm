<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_menu_groups') || ! Schema::hasTable('crm_menu_items')) {
            return;
        }

        $now = now();

        DB::table('crm_menu_groups')->updateOrInsert(
            ['menu_key' => 'accounting'],
            [
                'title' => 'Comptabilité',
                'active' => true,
                'sort_order' => 18,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')
                ->where('slug', 'controle-caisse')
                ->update([
                    'name' => 'Contrôle caisse',
                    'route_path' => '/controle-caisse',
                    'active' => true,
                    'sort_order' => 25,
                    'updated_at' => $now,
                ]);
        }

        DB::table('crm_menu_items')->updateOrInsert(
            ['item_key' => 'module:controle-caisse'],
            [
                'group_key' => 'accounting',
                'icon_key' => 'creditCard',
                'label' => 'Contrôle caisse',
                'active' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_menu_items')) {
            return;
        }

        DB::table('crm_menu_items')
            ->where('item_key', 'module:controle-caisse')
            ->update([
                'group_key' => 'apps',
                'icon_key' => 'table',
                'label' => 'Controle caisse',
                'sort_order' => 25,
                'updated_at' => now(),
            ]);

        if (Schema::hasTable('crm_menu_groups')) {
            DB::table('crm_menu_groups')
                ->where('menu_key', 'accounting')
                ->whereNotExists(function ($query): void {
                    $query->select(DB::raw(1))
                        ->from('crm_menu_items')
                        ->whereColumn('crm_menu_items.group_key', 'crm_menu_groups.menu_key');
                })
                ->delete();
        }
    }
};
