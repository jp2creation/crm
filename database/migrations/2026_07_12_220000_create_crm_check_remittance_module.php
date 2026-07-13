<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_check_remittances')) {
            Schema::create('crm_check_remittances', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_id')->constrained('crm_sites')->cascadeOnDelete();
                $table->date('remittance_date')->index();
                $table->string('reference', 80)->nullable()->index();
                $table->string('bank_name', 120)->nullable();
                $table->string('status', 30)->default('draft')->index();
                $table->unsignedInteger('check_count')->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->timestamps();

                $table->index(['site_id', 'remittance_date']);
            });
        }

        if (! Schema::hasTable('crm_check_remittance_lines')) {
            Schema::create('crm_check_remittance_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('check_remittance_id')->constrained('crm_check_remittances')->cascadeOnDelete();
                $table->string('payer_name', 190)->nullable();
                $table->string('invoice_number', 80)->nullable()->index();
                $table->string('check_number', 80)->nullable();
                $table->string('bank_name', 120)->nullable();
                $table->date('check_date')->nullable()->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('photo_path', 255)->nullable();
                $table->string('original_name', 160)->nullable();
                $table->string('mime_type', 80)->nullable();
                $table->unsignedInteger('size')->default(0);
                $table->longText('ocr_text')->nullable();
                $table->decimal('ocr_confidence', 5, 2)->nullable();
                $table->unsignedInteger('sort_order')->default(100);
                $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->timestamps();

                $table->index(['check_remittance_id', 'sort_order']);
            });
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->updateOrInsert(
                ['slug' => 'remise-cheques'],
                [
                    'name' => 'Remise de chèques',
                    'description' => 'Remises de chèques, photos, contrôle des montants et impression PDF.',
                    'route_path' => '/remise-cheques',
                    'active' => true,
                    'sort_order' => 27,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_menu_items')) {
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
        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')->where('item_key', 'module:remise-cheques')->delete();
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->where('slug', 'remise-cheques')->delete();
        }

        if (Schema::hasTable('crm_permissions')) {
            DB::table('crm_permissions')->whereIn('name', [
                'check_remittances.view',
                'check_remittances.manage',
            ])->delete();
        }

        Schema::dropIfExists('crm_check_remittance_lines');
        Schema::dropIfExists('crm_check_remittances');
    }
};
