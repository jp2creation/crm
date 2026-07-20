<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_mobile_refresh_tokens')) {
            return;
        }

        Schema::table('crm_mobile_refresh_tokens', function (Blueprint $table): void {
            if (! Schema::hasColumn('crm_mobile_refresh_tokens', 'family_id')) {
                $table->uuid('family_id')->nullable()->after('token_hash');
            }

            if (! Schema::hasColumn('crm_mobile_refresh_tokens', 'revoked_reason')) {
                $table->string('revoked_reason', 80)->nullable()->after('revoked_at');
            }
        });

        $this->addIndex('crm_mobile_refresh_tokens', 'crm_mobile_refresh_tokens_family_revoked_idx', ['family_id', 'revoked_at']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_mobile_refresh_tokens')) {
            return;
        }

        $this->dropIndex('crm_mobile_refresh_tokens', 'crm_mobile_refresh_tokens_family_revoked_idx');

        Schema::table('crm_mobile_refresh_tokens', function (Blueprint $table): void {
            if (Schema::hasColumn('crm_mobile_refresh_tokens', 'revoked_reason')) {
                $table->dropColumn('revoked_reason');
            }

            if (Schema::hasColumn('crm_mobile_refresh_tokens', 'family_id')) {
                $table->dropColumn('family_id');
            }
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addIndex(string $table, string $name, array $columns): void
    {
        if (! $this->canUseColumns($table, $columns) || $this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
            $blueprint->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! $this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropIndex($name);
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function canUseColumns(string $table, array $columns): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => strcasecmp((string) ($index['name'] ?? ''), $name) === 0);
    }
};
