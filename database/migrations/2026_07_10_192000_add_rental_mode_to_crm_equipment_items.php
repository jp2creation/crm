<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_equipment_items') || Schema::hasColumn('crm_equipment_items', 'rental_mode')) {
            return;
        }

        Schema::table('crm_equipment_items', function (Blueprint $table): void {
            $column = $table->string('rental_mode', 32)->default('half_day_and_day');

            if (Schema::hasColumn('crm_equipment_items', 'show_day_price')) {
                $column->after('show_day_price');
            } else {
                $column->after('day_price');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_equipment_items') || ! Schema::hasColumn('crm_equipment_items', 'rental_mode')) {
            return;
        }

        Schema::table('crm_equipment_items', function (Blueprint $table): void {
            $table->dropColumn('rental_mode');
        });
    }
};
