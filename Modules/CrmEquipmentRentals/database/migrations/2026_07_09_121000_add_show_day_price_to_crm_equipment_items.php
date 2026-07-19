<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_equipment_items') || Schema::hasColumn('crm_equipment_items', 'show_day_price')) {
            return;
        }

        Schema::table('crm_equipment_items', function (Blueprint $table): void {
            $table->boolean('show_day_price')->default(true)->after('day_price');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_equipment_items') || ! Schema::hasColumn('crm_equipment_items', 'show_day_price')) {
            return;
        }

        Schema::table('crm_equipment_items', function (Blueprint $table): void {
            $table->dropColumn('show_day_price');
        });
    }
};
