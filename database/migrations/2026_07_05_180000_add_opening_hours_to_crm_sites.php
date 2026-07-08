<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_sites')) {
            return;
        }

        Schema::table('crm_sites', function (Blueprint $table) {
            if (! Schema::hasColumn('crm_sites', 'morning_start')) {
                $table->time('morning_start')->default('07:30:00')->after('active');
            }
            if (! Schema::hasColumn('crm_sites', 'morning_end')) {
                $table->time('morning_end')->default('12:00:00')->after('morning_start');
            }
            if (! Schema::hasColumn('crm_sites', 'afternoon_start')) {
                $table->time('afternoon_start')->default('13:30:00')->after('morning_end');
            }
            if (! Schema::hasColumn('crm_sites', 'afternoon_end')) {
                $table->time('afternoon_end')->default('17:30:00')->after('afternoon_start');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_sites')) {
            return;
        }

        Schema::table('crm_sites', function (Blueprint $table) {
            foreach (['afternoon_end', 'afternoon_start', 'morning_end', 'morning_start'] as $column) {
                if (Schema::hasColumn('crm_sites', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
