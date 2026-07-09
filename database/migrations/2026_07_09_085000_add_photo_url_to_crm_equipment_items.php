<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_equipment_items') || Schema::hasColumn('crm_equipment_items', 'photo_url')) {
            return;
        }

        Schema::table('crm_equipment_items', function (Blueprint $table): void {
            $table->string('photo_url', 255)->nullable()->after('color');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_equipment_items') || ! Schema::hasColumn('crm_equipment_items', 'photo_url')) {
            return;
        }

        Schema::table('crm_equipment_items', function (Blueprint $table): void {
            $table->dropColumn('photo_url');
        });
    }
};
