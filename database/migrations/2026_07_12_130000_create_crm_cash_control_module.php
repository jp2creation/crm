<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_cash_register_days')) {
            Schema::create('crm_cash_register_days', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_id')->constrained('crm_sites')->cascadeOnDelete();
                $table->date('cash_date');
                $table->decimal('opening_balance', 12, 2)->default(0);
                $table->decimal('invoice_total', 12, 2)->default(0);
                $table->decimal('cash_sales', 12, 2)->default(0);
                $table->decimal('card_sales', 12, 2)->default(0);
                $table->decimal('check_sales', 12, 2)->default(0);
                $table->decimal('transfer_sales', 12, 2)->default(0);
                $table->decimal('counted_cash', 12, 2)->nullable();
                $table->decimal('bank_counted', 12, 2)->nullable();
                $table->unsignedInteger('invoice_errors_count')->default(0);
                $table->string('status', 20)->default('review');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();

                $table->unique(['site_id', 'cash_date']);
                $table->index(['site_id', 'status']);
                $table->index('cash_date');
            });
        }

        if (! Schema::hasTable('crm_cash_movements')) {
            Schema::create('crm_cash_movements', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('cash_register_day_id')->constrained('crm_cash_register_days')->cascadeOnDelete();
                $table->string('type', 20)->default('cash_out');
                $table->string('label', 190);
                $table->decimal('amount', 12, 2);
                $table->date('occurred_on');
                $table->integer('sort_order')->default(100);
                $table->string('justification_path', 255)->nullable();
                $table->string('original_name', 160)->nullable();
                $table->string('mime_type', 80)->nullable();
                $table->unsignedInteger('size')->default(0);
                $table->unsignedBigInteger('uploaded_by')->nullable()->index();
                $table->timestamps();

                $table->index(['cash_register_day_id', 'type']);
                $table->index('occurred_on');
            });
        }

        $now = now();

        if (Schema::hasTable('crm_menu_groups')) {
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
        }

        DB::table('crm_modules')->updateOrInsert(
            ['slug' => 'controle-caisse'],
            [
                'name' => 'Contrôle caisse',
                'description' => 'Controle journalier de caisse, reports, ecarts et justificatifs',
                'route_path' => '/controle-caisse',
                'menu_badge' => null,
                'show_menu_badge' => false,
                'active' => true,
                'sort_order' => 25,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')->updateOrInsert(
                ['item_key' => 'module:controle-caisse'],
                [
                    'group_key' => 'accounting',
                    'icon_key' => 'creditCard',
                    'label' => 'Contrôle caisse',
                    'active' => true,
                    'sort_order' => 25,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        $permissions = [
            ['controle_caisse.view', 'Voir le controle caisse', 'Controle caisse', 147],
            ['controle_caisse.manage', 'Gerer le controle caisse', 'Controle caisse', 148],
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

        $this->grantDefaultAccess($now);
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_cash_movements');
        Schema::dropIfExists('crm_cash_register_days');

        $moduleId = DB::table('crm_modules')->where('slug', 'controle-caisse')->value('id');
        if ($moduleId) {
            DB::table('crm_user_modules')->where('module_id', $moduleId)->delete();

            if (Schema::hasTable('crm_user_site_module_permissions')) {
                DB::table('crm_user_site_module_permissions')->where('module_id', $moduleId)->delete();
            }
        }

        $permissionIds = DB::table('crm_permissions')
            ->whereIn('name', ['controle_caisse.view', 'controle_caisse.manage'])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('crm_user_permissions')->whereIn('permission_id', $permissionIds)->delete();

            if (Schema::hasTable('crm_user_site_module_permissions')) {
                DB::table('crm_user_site_module_permissions')->whereIn('permission_id', $permissionIds)->delete();
            }
        }

        DB::table('crm_permissions')->whereIn('name', ['controle_caisse.view', 'controle_caisse.manage'])->delete();
        DB::table('crm_menu_items')->where('item_key', 'module:controle-caisse')->delete();
        DB::table('crm_modules')->where('slug', 'controle-caisse')->delete();
    }

    private function grantDefaultAccess($now): void
    {
        if (! Schema::hasTable('crm_users') || ! Schema::hasTable('crm_user_modules') || ! Schema::hasTable('crm_user_permissions')) {
            return;
        }

        $moduleId = (int) DB::table('crm_modules')->where('slug', 'controle-caisse')->value('id');
        $viewPermissionId = (int) DB::table('crm_permissions')->where('name', 'controle_caisse.view')->value('id');
        $managePermissionId = (int) DB::table('crm_permissions')->where('name', 'controle_caisse.manage')->value('id');

        if ($moduleId <= 0 || $viewPermissionId <= 0 || $managePermissionId <= 0) {
            return;
        }

        $activeUsers = DB::table('crm_users')
            ->where('active', true)
            ->where('role', '<>', 'blocked')
            ->get(['id', 'role']);

        foreach ($activeUsers as $user) {
            DB::table('crm_user_modules')->updateOrInsert(
                ['module_id' => $moduleId, 'user_id' => (int) $user->id],
                ['created_at' => $now],
            );

            DB::table('crm_user_permissions')->updateOrInsert(
                ['permission_id' => $viewPermissionId, 'user_id' => (int) $user->id],
                ['created_at' => $now],
            );

            if (in_array($user->role, ['admin', 'responsable'], true)) {
                DB::table('crm_user_permissions')->updateOrInsert(
                    ['permission_id' => $managePermissionId, 'user_id' => (int) $user->id],
                    ['created_at' => $now],
                );
            }
        }
    }
};
