<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->where('slug', 'tournees-representants')->update([
                'name' => 'Rapport de visite',
                'description' => 'Planning, visites clients et rapports de visite.',
                'route_path' => '/rapport-visite',
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')->where('item_key', 'module:tournees-representants')->update([
                'label' => 'Rapport de visite',
                'group_key' => 'apps',
                'icon_key' => 'calendar',
                'active' => true,
                'sort_order' => 17,
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('crm_permissions')) {
            foreach ([
                'sales_tours.view' => ['Voir les rapports de visite', 'Rapport de visite'],
                'sales_tours.create' => ['Créer un rapport de visite', 'Rapport de visite'],
                'sales_tours.report' => ['Renseigner les rapports de visite', 'Rapport de visite'],
                'sales_tours.manage' => ['Gérer tous les rapports de visite', 'Rapport de visite'],
            ] as $name => [$label, $group]) {
                DB::table('crm_permissions')->where('name', $name)->update([
                    'label' => $label,
                    'group_label' => $group,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->where('slug', 'tournees-representants')->update([
                'name' => 'Tournées représentants',
                'description' => 'Planning des tournées, visites clients et rapports commerciaux.',
                'route_path' => '/tournees-representants',
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')->where('item_key', 'module:tournees-representants')->update([
                'label' => 'Tournées représentants',
                'updated_at' => now(),
            ]);
        }
    }
};
