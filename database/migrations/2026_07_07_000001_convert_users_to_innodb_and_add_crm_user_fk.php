<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasTable('crm_users')
            || ! Schema::hasColumn('crm_users', 'user_id')
        ) {
            return;
        }

        $this->convertTableToInnoDb('users');
        $this->convertTableToInnoDb('crm_users');

        if (! $this->foreignKeyExists('crm_users', 'crm_users_user_id_foreign')) {
            Schema::table('crm_users', function (Blueprint $table): void {
                $table->foreign('user_id', 'crm_users_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('crm_users')
            || ! Schema::hasColumn('crm_users', 'user_id')
            || ! $this->foreignKeyExists('crm_users', 'crm_users_user_id_foreign')
        ) {
            return;
        }

        Schema::table('crm_users', function (Blueprint $table): void {
            $table->dropForeign('crm_users_user_id_foreign');
        });
    }

    private function convertTableToInnoDb(string $table): void
    {
        $engine = DB::table('information_schema.TABLES')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->value('ENGINE');

        if (strtoupper((string) $engine) !== 'INNODB') {
            DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
