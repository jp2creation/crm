<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_equipment_categories')) {
            Schema::create('crm_equipment_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120)->unique();
                $table->string('slug', 140)->unique();
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(100);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('crm_equipment_items')) {
            Schema::create('crm_equipment_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id');
                $table->foreignId('category_id')->nullable();
                $table->string('name', 160);
                $table->string('inventory_code', 80)->nullable();
                $table->string('description')->default('');
                $table->string('color', 20)->default('#95002e');
                $table->decimal('half_day_price', 10, 2)->default(0);
                $table->decimal('day_price', 10, 2)->default(0);
                $table->decimal('deposit_amount', 10, 2)->default(0);
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(100);
                $table->timestamps();

                $table->index(['site_id', 'active']);
                $table->index(['category_id', 'active']);
            });
        }

        if (! Schema::hasTable('crm_equipment_rentals')) {
            Schema::create('crm_equipment_rentals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id');
                $table->foreignId('equipment_item_id');
                $table->foreignId('user_id');
                $table->string('user_name', 160);
                $table->string('period_type', 20)->default('half_day');
                $table->string('slot', 20)->default('morning');
                $table->string('status', 20)->default('reserved');
                $table->string('title', 190)->default('');
                $table->string('contact_phone', 40)->default('');
                $table->dateTime('start_at');
                $table->dateTime('end_at');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['equipment_item_id', 'start_at', 'end_at']);
                $table->index(['site_id', 'start_at']);
                $table->index(['status', 'start_at']);
            });
        }

        $this->seedModuleAndPermissions();
        $this->seedEquipment();
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_equipment_rentals');
        Schema::dropIfExists('crm_equipment_items');
        Schema::dropIfExists('crm_equipment_categories');
    }

    private function seedModuleAndPermissions(): void
    {
        if (! Schema::hasTable('crm_permissions') || ! Schema::hasTable('crm_modules')) {
            return;
        }

        $now = now();
        $permissions = [
            ['equipment_rentals.view', 'Voir les locations materiel', 'Location materiel', 120],
            ['equipment_rentals.create', 'Creer une location materiel', 'Location materiel', 130],
            ['equipment_rentals.update_own', 'Modifier ses locations materiel', 'Location materiel', 140],
            ['equipment_rentals.update_any', 'Modifier toutes les locations materiel', 'Location materiel', 150],
            ['equipment_rentals.delete_own', 'Supprimer ses locations materiel', 'Location materiel', 160],
            ['equipment_rentals.delete_any', 'Supprimer toutes les locations materiel', 'Location materiel', 170],
            ['equipment_rentals.manage_items', 'Gerer le materiel de location', 'Location materiel', 180],
        ];

        foreach ($permissions as [$name, $label, $group, $sortOrder]) {
            DB::table('crm_permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'label' => $label,
                    'group_label' => $group,
                    'sort_order' => $sortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        DB::table('crm_modules')->updateOrInsert(
            ['slug' => 'locations-materiel'],
            [
                'name' => 'Location materiel',
                'description' => 'Planning et locations du materiel interne',
                'route_path' => '/locations-materiel',
                'active' => true,
                'sort_order' => 15,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')->updateOrInsert(
                ['item_key' => 'module:locations-materiel'],
                [
                    'group_key' => 'internal',
                    'icon_key' => 'package',
                    'label' => 'Location materiel',
                    'active' => true,
                    'sort_order' => 15,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        $this->grantDefaultAccess($permissions);
    }

    private function grantDefaultAccess(array $permissions): void
    {
        if (! Schema::hasTable('crm_users') || ! Schema::hasTable('crm_user_modules') || ! Schema::hasTable('crm_user_permissions')) {
            return;
        }

        $moduleId = DB::table('crm_modules')->where('slug', 'locations-materiel')->value('id');
        if (! $moduleId) {
            return;
        }

        $permissionIds = DB::table('crm_permissions')
            ->whereIn('name', array_column($permissions, 0))
            ->pluck('id', 'name')
            ->all();

        $profiles = [
            'user' => [
                'equipment_rentals.view',
                'equipment_rentals.create',
                'equipment_rentals.update_own',
                'equipment_rentals.delete_own',
            ],
            'responsable' => [
                'equipment_rentals.view',
                'equipment_rentals.create',
                'equipment_rentals.update_own',
                'equipment_rentals.update_any',
                'equipment_rentals.delete_own',
                'equipment_rentals.delete_any',
                'equipment_rentals.manage_items',
            ],
            'admin' => array_column($permissions, 0),
        ];

        $now = now();
        $users = DB::table('crm_users')
            ->where('role', '<>', 'blocked')
            ->get(['id', 'role']);

        foreach ($users as $user) {
            DB::table('crm_user_modules')->updateOrInsert(
                ['module_id' => $moduleId, 'user_id' => $user->id],
                ['created_at' => $now],
            );

            foreach ($profiles[$user->role] ?? $profiles['user'] as $permissionName) {
                $permissionId = $permissionIds[$permissionName] ?? null;
                if (! $permissionId) {
                    continue;
                }

                DB::table('crm_user_permissions')->updateOrInsert(
                    ['permission_id' => $permissionId, 'user_id' => $user->id],
                    ['created_at' => $now],
                );
            }
        }
    }

    private function seedEquipment(): void
    {
        if (! Schema::hasTable('crm_sites')) {
            return;
        }

        $siteId = DB::table('crm_sites')->where('name', 'Palissy')->value('id')
            ?: DB::table('crm_sites')->orderBy('id')->value('id');

        if (! $siteId) {
            return;
        }

        $now = now();
        $categoryId = DB::table('crm_equipment_categories')->where('slug', 'poncage')->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('crm_equipment_categories')->insertGetId([
                'name' => 'Poncage',
                'slug' => 'poncage',
                'active' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $items = [
            ['Ponceuse parquet', 'PON-PARQUET', 'Ponceuse principale pour parquet', '#95002e', 45, 80, 300, 10],
            ['Bordureuse', 'BOR-001', 'Bordureuse pour finitions et plinthes', '#f59e0b', 35, 60, 200, 20],
            ['Aspirateur chantier', 'ASP-001', 'Aspirateur poussiere pour poncage', '#1d354f', 25, 40, 150, 30],
        ];

        foreach ($items as [$name, $code, $description, $color, $halfDayPrice, $dayPrice, $deposit, $sortOrder]) {
            DB::table('crm_equipment_items')->updateOrInsert(
                ['inventory_code' => $code],
                [
                    'site_id' => $siteId,
                    'category_id' => $categoryId,
                    'name' => $name,
                    'description' => $description,
                    'color' => $color,
                    'half_day_price' => $halfDayPrice,
                    'day_price' => $dayPrice,
                    'deposit_amount' => $deposit,
                    'active' => true,
                    'sort_order' => $sortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
};
