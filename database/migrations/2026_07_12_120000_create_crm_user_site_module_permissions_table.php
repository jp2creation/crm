<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_user_site_module_permissions')) {
            return;
        }

        Schema::create('crm_user_site_module_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('crm_users')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('crm_sites')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('crm_modules')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('crm_permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['user_id', 'site_id', 'module_id', 'permission_id'],
                'crm_user_site_module_permission_unique',
            );
            $table->index(['user_id', 'site_id'], 'crm_user_site_module_permissions_user_site_idx');
            $table->index(['site_id', 'module_id'], 'crm_user_site_module_permissions_site_module_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_user_site_module_permissions');
    }
};
