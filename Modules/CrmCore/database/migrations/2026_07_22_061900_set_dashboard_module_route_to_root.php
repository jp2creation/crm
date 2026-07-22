<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CrmCore\Support\CrmReferenceCache;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_modules')) {
            return;
        }

        DB::table('crm_modules')
            ->where('slug', 'dashboard')
            ->update([
                'route_path' => '/',
                'updated_at' => now(),
            ]);

        CrmReferenceCache::forgetModules();
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_modules')) {
            return;
        }

        DB::table('crm_modules')
            ->where('slug', 'dashboard')
            ->update([
                'route_path' => '/dashboard/crm',
                'updated_at' => now(),
            ]);

        CrmReferenceCache::forgetModules();
    }
};
