<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_sales_invoices')) {
            Schema::create('crm_sales_invoices', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_id')->constrained('crm_sites')->cascadeOnDelete();
                $table->foreignId('representative_user_id')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->string('external_id', 190)->nullable()->unique();
                $table->string('number', 120);
                $table->string('customer_name', 190)->index();
                $table->string('customer_reference', 120)->nullable()->index();
                $table->date('issue_date')->index();
                $table->date('due_date')->nullable()->index();
                $table->string('status', 30)->default('pending')->index();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->decimal('margin', 12, 2)->default(0);
                $table->decimal('commission_base', 12, 2)->default(0);
                $table->json('raw_data')->nullable();
                $table->timestamp('synced_at')->nullable()->index();
                $table->timestamps();

                $table->index(['site_id', 'issue_date']);
                $table->index(['site_id', 'representative_user_id', 'issue_date'], 'crm_sales_invoices_site_rep_date_idx');
                $table->index(['site_id', 'status', 'issue_date'], 'crm_sales_invoices_site_status_date_idx');
            });
        }

        if (! Schema::hasTable('crm_sales_objectives')) {
            Schema::create('crm_sales_objectives', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_id')->constrained('crm_sites')->cascadeOnDelete();
                $table->foreignId('representative_user_id')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->date('period_start')->index();
                $table->date('period_end')->index();
                $table->decimal('target_revenue', 12, 2)->default(0);
                $table->decimal('target_margin', 12, 2)->default(0);
                $table->unsignedSmallInteger('target_visits')->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->timestamps();

                $table->index(['site_id', 'period_start', 'period_end']);
                $table->index(['site_id', 'representative_user_id', 'period_start'], 'crm_sales_objectives_site_rep_period_idx');
            });
        }

        if (! Schema::hasTable('crm_sales_commissions')) {
            Schema::create('crm_sales_commissions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_id')->constrained('crm_sites')->cascadeOnDelete();
                $table->foreignId('representative_user_id')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('crm_sales_invoices')->nullOnDelete();
                $table->date('period_start')->index();
                $table->date('period_end')->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('status', 30)->default('pending')->index();
                $table->timestamp('paid_at')->nullable()->index();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->timestamps();

                $table->index(['site_id', 'period_start', 'period_end']);
                $table->index(['site_id', 'representative_user_id', 'period_start'], 'crm_sales_commissions_site_rep_period_idx');
                $table->index(['site_id', 'status', 'period_start'], 'crm_sales_commissions_site_status_period_idx');
            });
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->updateOrInsert(
                ['slug' => 'pilotage-commercial'],
                [
                    'name' => 'Pilotage commercial',
                    'description' => 'Objectifs, chiffre d affaires, factures et commissions commerciales.',
                    'route_path' => '/pilotage-commercial',
                    'active' => true,
                    'sort_order' => 19,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')->updateOrInsert(
                ['item_key' => 'module:pilotage-commercial'],
                [
                    'group_key' => 'apps',
                    'icon_key' => 'dashboard',
                    'label' => 'Pilotage commercial',
                    'active' => true,
                    'sort_order' => 19,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_permissions')) {
            foreach ([
                ['sales.view', 'Voir le pilotage commercial', 'Pilotage commercial', 163],
                ['sales.sync', 'Synchroniser les donnees commerciales', 'Pilotage commercial', 164],
                ['sales.manage', 'Gerer les objectifs commerciaux', 'Pilotage commercial', 165],
                ['sales.commissions', 'Gerer les commissions commerciales', 'Pilotage commercial', 166],
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
            DB::table('crm_menu_items')->where('item_key', 'module:pilotage-commercial')->delete();
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->where('slug', 'pilotage-commercial')->delete();
        }

        if (Schema::hasTable('crm_permissions')) {
            DB::table('crm_permissions')->whereIn('name', [
                'sales.view',
                'sales.sync',
                'sales.manage',
                'sales.commissions',
            ])->delete();
        }

        Schema::dropIfExists('crm_sales_commissions');
        Schema::dropIfExists('crm_sales_objectives');
        Schema::dropIfExists('crm_sales_invoices');
    }
};
