<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_deposit_requests')) {
            Schema::create('crm_deposit_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_id')->constrained('crm_sites')->cascadeOnDelete();
                $table->date('request_date')->index();
                $table->foreignId('requester_user_id')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->string('requester_name', 190)->index();
                $table->string('document_number', 120)->index();
                $table->string('client_name', 190)->nullable()->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('status', 30)->default('pending')->index();
                $table->timestamp('validated_at')->nullable()->index();
                $table->foreignId('validated_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('crm_users')->nullOnDelete();
                $table->timestamps();

                $table->index(['site_id', 'request_date']);
                $table->index(['site_id', 'status']);
            });
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->updateOrInsert(
                ['slug' => 'demandes-acompte'],
                [
                    'name' => "Demande d'acompte",
                    'description' => "Demandes d'acompte et validation par la comptabilité.",
                    'route_path' => '/demandes-acompte',
                    'active' => true,
                    'sort_order' => 26,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_menu_items')) {
            DB::table('crm_menu_items')->updateOrInsert(
                ['item_key' => 'module:demandes-acompte'],
                [
                    'group_key' => 'accounting',
                    'icon_key' => 'banknote',
                    'label' => "Demande d'acompte",
                    'active' => true,
                    'sort_order' => 26,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('crm_permissions')) {
            foreach ([
                ['deposit_requests.view', "Voir les demandes d'acompte", "Demande d'acompte", 151],
                ['deposit_requests.create', "Créer une demande d'acompte", "Demande d'acompte", 152],
                ['deposit_requests.manage', "Gérer les demandes d'acompte", "Demande d'acompte", 153],
                ['deposit_requests.validate', "Valider les demandes d'acompte", "Demande d'acompte", 154],
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
            DB::table('crm_menu_items')->where('item_key', 'module:demandes-acompte')->delete();
        }

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->where('slug', 'demandes-acompte')->delete();
        }

        if (Schema::hasTable('crm_permissions')) {
            DB::table('crm_permissions')->whereIn('name', [
                'deposit_requests.view',
                'deposit_requests.create',
                'deposit_requests.manage',
                'deposit_requests.validate',
            ])->delete();
        }

        Schema::dropIfExists('crm_deposit_requests');
    }
};
