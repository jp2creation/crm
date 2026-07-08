<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_pages')) {
            return;
        }

        $engine = DB::table('information_schema.TABLES')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'crm_pages')
            ->value('ENGINE');

        if (strtoupper((string) $engine) !== 'INNODB') {
            DB::statement('ALTER TABLE crm_pages ENGINE=InnoDB');
        }
    }

    public function down(): void
    {
        // Keep the table on InnoDB; rolling back to MyISAM would remove transactional guarantees.
    }
};
