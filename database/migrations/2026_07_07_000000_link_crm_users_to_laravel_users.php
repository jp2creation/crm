<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_users') || ! Schema::hasTable('users')) {
            return;
        }

        Schema::table('crm_users', function (Blueprint $table): void {
            if (! Schema::hasColumn('crm_users', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id');
            }
        });

        if (! $this->hasIndex('crm_users', 'crm_users_user_id_unique')) {
            Schema::table('crm_users', function (Blueprint $table): void {
                $table->unique('user_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_users') || ! Schema::hasColumn('crm_users', 'user_id')) {
            return;
        }

        Schema::table('crm_users', function (Blueprint $table): void {
            if ($this->hasIndex('crm_users', 'crm_users_user_id_unique')) {
                $table->dropUnique('crm_users_user_id_unique');
            }

            $table->dropColumn('user_id');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return (int) DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->count() > 0;
    }
};
