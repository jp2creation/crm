<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_leave_employees')) {
            Schema::create('crm_leave_employees', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('crm_user_id')->nullable()->index();
                $table->string('name', 160);
                $table->string('slug', 160)->unique();
                $table->string('color', 20)->default('#f59e0b');
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(100);
                $table->timestamps();

                $table->index(['active', 'sort_order']);
            });
        }

        if (! Schema::hasTable('crm_leave_entries')) {
            Schema::create('crm_leave_entries', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('employee_id')->index();
                $table->date('start_date')->index();
                $table->date('end_date')->index();
                $table->string('type', 40)->default('conge');
                $table->string('period', 20)->default('full');
                $table->string('status', 30)->default('approved');
                $table->text('notes')->nullable();
                $table->string('source', 80)->default('crm');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'start_date', 'end_date']);
                $table->index(['status', 'type']);
            });
        }

        $now = now();

        DB::table('crm_modules')->updateOrInsert(
            ['slug' => 'conges'],
            [
                'name' => 'Conges',
                'description' => 'Planning et gestion des conges de l equipe Palissy',
                'route_path' => '/conges',
                'menu_badge' => null,
                'show_menu_badge' => false,
                'active' => true,
                'sort_order' => 24,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        DB::table('crm_menu_items')->updateOrInsert(
            ['item_key' => 'module:conges'],
            [
                'group_key' => 'internal',
                'icon_key' => 'calendar',
                'label' => 'Conges',
                'active' => true,
                'sort_order' => 24,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $permissions = [
            ['conges.view', 'Voir les conges', 'Conges', 185],
            ['conges.manage', 'Gerer les conges', 'Conges', 186],
        ];

        foreach ($permissions as [$name, $label, $group, $sort]) {
            DB::table('crm_permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'label' => $label,
                    'group_label' => $group,
                    'sort_order' => $sort,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        $moduleId = (int) DB::table('crm_modules')->where('slug', 'conges')->value('id');
        $viewPermissionId = (int) DB::table('crm_permissions')->where('name', 'conges.view')->value('id');
        $managePermissionId = (int) DB::table('crm_permissions')->where('name', 'conges.manage')->value('id');

        $activeUserIds = DB::table('crm_users')
            ->where('active', true)
            ->where('role', '<>', 'blocked')
            ->pluck('id');

        foreach ($activeUserIds as $userId) {
            DB::table('crm_user_modules')->updateOrInsert(
                ['module_id' => $moduleId, 'user_id' => (int) $userId],
                ['created_at' => $now],
            );

            DB::table('crm_user_permissions')->updateOrInsert(
                ['permission_id' => $viewPermissionId, 'user_id' => (int) $userId],
                ['created_at' => $now],
            );
        }

        $managerUserIds = DB::table('crm_users')
            ->where('active', true)
            ->whereIn('role', ['admin', 'responsable'])
            ->pluck('id');

        foreach ($managerUserIds as $userId) {
            DB::table('crm_user_permissions')->updateOrInsert(
                ['permission_id' => $managePermissionId, 'user_id' => (int) $userId],
                ['created_at' => $now],
            );
        }

        $this->seedEmployees($now);
        $this->seedExcelLeaves($now);
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leave_entries');
        Schema::dropIfExists('crm_leave_employees');

        $moduleId = DB::table('crm_modules')->where('slug', 'conges')->value('id');
        if ($moduleId) {
            DB::table('crm_user_modules')->where('module_id', $moduleId)->delete();
        }

        $permissionIds = DB::table('crm_permissions')
            ->whereIn('name', ['conges.view', 'conges.manage'])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('crm_user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('crm_permissions')->whereIn('name', ['conges.view', 'conges.manage'])->delete();
        DB::table('crm_menu_items')->where('item_key', 'module:conges')->delete();
        DB::table('crm_modules')->where('slug', 'conges')->delete();
    }

    private function seedEmployees($now): void
    {
        $users = DB::table('crm_users')->pluck('id', 'name');

        $employees = [
            ['PHILIPPE', 'philippe', '#2563eb', 10, 'Philippe P'],
            ['REMI', 'remi', '#16a34a', 20, 'Remi G'],
            ['JEREMY', 'jeremy', '#64748b', 30, 'Jeremy L'],
            ['CHRISTOPHE', 'christophe', '#dc2626', 40, 'Christophe L'],
            ['SAMI', 'sami', '#9333ea', 50, 'Samy I'],
            ['JEAN-PHILIPPE', 'jean-philippe', '#f59e0b', 60, 'J-Philippe'],
        ];

        foreach ($employees as [$name, $slug, $color, $sortOrder, $crmName]) {
            DB::table('crm_leave_employees')->updateOrInsert(
                ['slug' => $slug],
                [
                    'crm_user_id' => $users[$crmName] ?? null,
                    'name' => $name,
                    'color' => $color,
                    'active' => true,
                    'sort_order' => $sortOrder,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    private function seedExcelLeaves($now): void
    {
        $ranges = [
            ['jean-philippe', '2025-11-07', '2025-11-08'],
            ['philippe', '2025-11-10', '2025-11-10'],
            ['remi', '2025-11-25', '2025-11-26'],
            ['philippe', '2025-12-29', '2026-01-03'],
            ['jean-philippe', '2026-04-13', '2026-04-18'],
            ['christophe', '2026-05-11', '2026-05-16'],
            ['christophe', '2026-05-18', '2026-05-23'],
            ['philippe', '2026-05-25', '2026-05-30'],
            ['remi', '2026-07-07', '2026-07-08'],
            ['sami', '2026-07-10', '2026-07-10'],
            ['remi', '2026-07-13', '2026-07-13'],
        ];

        $employeeIds = DB::table('crm_leave_employees')->pluck('id', 'slug');

        foreach ($ranges as [$slug, $startDate, $endDate]) {
            $employeeId = (int) ($employeeIds[$slug] ?? 0);
            if ($employeeId <= 0) {
                continue;
            }

            $exists = DB::table('crm_leave_entries')
                ->where('employee_id', $employeeId)
                ->where('start_date', $startDate)
                ->where('end_date', $endDate)
                ->where('source', 'excel')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('crm_leave_entries')->insert([
                'employee_id' => $employeeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'conge',
                'period' => 'full',
                'status' => 'approved',
                'notes' => 'Import depuis Conges-Palissy.xlsx',
                'source' => 'excel',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
