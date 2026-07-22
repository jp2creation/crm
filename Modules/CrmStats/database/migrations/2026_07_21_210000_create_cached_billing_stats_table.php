<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cached_billing_stats')) {
            Schema::create('cached_billing_stats', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_id')->nullable()->constrained('crm_sites')->nullOnDelete();
                $table->date('date');
                $table->string('client_id', 190);
                $table->string('client_name', 190);
                $table->string('client_status', 30)->default('active');
                $table->string('product_family', 120)->default('Non classe');
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->unsignedInteger('invoice_count')->default(0);
                $table->decimal('quantity', 14, 3)->default(0);
                $table->date('last_invoice_date')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();

                $table->unique(
                    ['site_id', 'date', 'client_id', 'product_family'],
                    'cached_billing_stats_unique_day_client_family',
                );
                $table->index(['date', 'client_id'], 'cached_billing_stats_date_client_idx');
                $table->index(['date', 'product_family'], 'cached_billing_stats_date_family_idx');
                $table->index(['site_id', 'date'], 'cached_billing_stats_site_date_idx');
                $table->index(['client_status', 'date'], 'cached_billing_stats_status_date_idx');
                $table->index('last_invoice_date');
            });
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->updateOrInsert(
                ['slug' => 'stats'],
                [
                    'name' => 'Stats',
                    'description' => 'Indicateurs commerciaux alimentes par l API de facturation.',
                    'route_path' => '/admin/stats',
                    'active' => true,
                    'sort_order' => 20,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_permissions')) {
            foreach ([
                ['stats.view', 'Voir les statistiques commerciales', 'Stats', 170],
                ['stats.sync', 'Synchroniser les statistiques commerciales', 'Stats', 171],
                ['stats.manage', 'Gerer les statistiques commerciales', 'Stats', 172],
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

        if (Schema::hasTable('permissions')) {
            foreach (['view_stats', 'sync_stats', 'manage_stats'] as $name) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => $name, 'guard_name' => 'web'],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->where('guard_name', 'web')
                ->whereIn('name', ['view_stats', 'sync_stats', 'manage_stats'])
                ->delete();
        }

        if (Schema::hasTable('crm_permissions')) {
            DB::table('crm_permissions')
                ->whereIn('name', ['stats.view', 'stats.sync', 'stats.manage'])
                ->delete();
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->where('slug', 'stats')->delete();
        }

        Schema::dropIfExists('cached_billing_stats');
    }
};
