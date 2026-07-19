<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_vehicles')) {
            return;
        }

        Schema::table('crm_vehicles', function (Blueprint $table): void {
            if (! Schema::hasColumn('crm_vehicles', 'day_start_time')) {
                $table->string('day_start_time', 5)->nullable()->after('photo_url');
            }

            if (! Schema::hasColumn('crm_vehicles', 'day_end_time')) {
                $table->string('day_end_time', 5)->nullable()->after('day_start_time');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_vehicles')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('crm_vehicles', 'day_start_time') ? 'day_start_time' : null,
            Schema::hasColumn('crm_vehicles', 'day_end_time') ? 'day_end_time' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('crm_vehicles', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
