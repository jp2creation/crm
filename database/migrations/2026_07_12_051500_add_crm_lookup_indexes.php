<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, array{table: string, name: string, columns: array<int, string>}>
     */
    private array $indexes = [
        [
            'table' => 'crm_user_sites',
            'name' => 'crm_user_sites_user_id_idx',
            'columns' => ['user_id'],
        ],
        [
            'table' => 'crm_user_modules',
            'name' => 'crm_user_modules_user_id_idx',
            'columns' => ['user_id'],
        ],
        [
            'table' => 'crm_user_permissions',
            'name' => 'crm_user_permissions_user_id_idx',
            'columns' => ['user_id'],
        ],
        [
            'table' => 'crm_logs',
            'name' => 'crm_logs_user_id_idx',
            'columns' => ['user_id'],
        ],
        [
            'table' => 'crm_leave_entries',
            'name' => 'crm_leave_entries_created_by_idx',
            'columns' => ['created_by'],
        ],
        [
            'table' => 'crm_leave_entries',
            'name' => 'crm_leave_entries_updated_by_idx',
            'columns' => ['updated_by'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $index) {
            $this->addIndex($index['table'], $index['name'], $index['columns']);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->indexes) as $index) {
            $this->dropIndex($index['table'], $index['name']);
        }
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
