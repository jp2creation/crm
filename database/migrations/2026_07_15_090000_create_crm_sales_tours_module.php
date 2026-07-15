<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_sales_tours')) {
            Schema::create('crm_sales_tours', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_id')->constrained('crm_sites')->cascadeOnDelete();
                $table->foreignId('representative_user_id')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->string('title', 190);
                $table->date('tour_date')->index();
                $table->string('status', 30)->default('planned')->index();
                $table->text('objective')->nullable();
                $table->text('report_summary')->nullable();
                $table->text('report_next_actions')->nullable();
                $table->string('report_mood', 30)->nullable();
                $table->decimal('kilometers', 8, 1)->default(0);
                $table->timestamp('completed_at')->nullable()->index();
                $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->timestamps();

                $table->index(['site_id', 'tour_date']);
                $table->index(['site_id', 'representative_user_id', 'tour_date'], 'crm_sales_tours_site_rep_date_idx');
            });
        }

        if (! Schema::hasTable('crm_sales_visits')) {
            Schema::create('crm_sales_visits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tour_id')->constrained('crm_sales_tours')->cascadeOnDelete();
                $table->foreignId('site_id')->constrained('crm_sites')->cascadeOnDelete();
                $table->foreignId('representative_user_id')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->string('customer_name', 190)->index();
                $table->string('customer_reference', 120)->nullable()->index();
                $table->string('address', 255)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('city', 120)->nullable()->index();
                $table->string('contact_name', 160)->nullable();
                $table->string('contact_phone', 80)->nullable();
                $table->string('contact_email', 190)->nullable();
                $table->dateTime('planned_at')->nullable()->index();
                $table->unsignedSmallInteger('duration_minutes')->default(45);
                $table->string('visit_type', 40)->default('client')->index();
                $table->string('priority', 20)->default('normal')->index();
                $table->string('status', 30)->default('planned')->index();
                $table->text('objective')->nullable();
                $table->text('result')->nullable();
                $table->text('next_action')->nullable();
                $table->date('next_action_date')->nullable()->index();
                $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->timestamps();

                $table->index(['site_id', 'planned_at']);
                $table->index(['tour_id', 'status']);
                $table->index(['site_id', 'representative_user_id', 'planned_at'], 'crm_sales_visits_site_rep_planned_idx');
            });
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->updateOrInsert(
                ['slug' => 'tournees-representants'],
                [
                    'name' => 'Rapport de visite',
                    'description' => 'Planning, visites clients et rapports de visite.',
                    'route_path' => '/rapport-visite',
                    'active' => true,
                    'sort_order' => 17,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')->updateOrInsert(
                ['item_key' => 'module:tournees-representants'],
                [
                    'group_key' => 'apps',
                    'icon_key' => 'calendar',
                    'label' => 'Rapport de visite',
                    'active' => true,
                    'sort_order' => 17,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_permissions')) {
            foreach ([
                ['sales_tours.view', 'Voir les rapports de visite', 'Rapport de visite', 158],
                ['sales_tours.create', 'Créer un rapport de visite', 'Rapport de visite', 159],
                ['sales_tours.report', 'Renseigner les rapports de visite', 'Rapport de visite', 161],
                ['sales_tours.manage', 'Gérer tous les rapports de visite', 'Rapport de visite', 162],
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
        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')->where('item_key', 'module:tournees-representants')->delete();
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->where('slug', 'tournees-representants')->delete();
        }

        if (Schema::hasTable('crm_permissions')) {
            DB::table('crm_permissions')->whereIn('name', [
                'sales_tours.view',
                'sales_tours.create',
                'sales_tours.report',
                'sales_tours.manage',
            ])->delete();
        }

        Schema::dropIfExists('crm_sales_visits');
        Schema::dropIfExists('crm_sales_tours');
    }
};
