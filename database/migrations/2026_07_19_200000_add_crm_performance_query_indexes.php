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
            'table' => 'crm_sites',
            'name' => 'crm_sites_active_name_idx',
            'columns' => ['active', 'name'],
        ],
        [
            'table' => 'crm_modules',
            'name' => 'crm_modules_active_sort_name_idx',
            'columns' => ['active', 'sort_order', 'name'],
        ],
        [
            'table' => 'crm_menu_items',
            'name' => 'crm_menu_items_active_item_key_idx',
            'columns' => ['active', 'item_key'],
        ],
        [
            'table' => 'crm_permissions',
            'name' => 'crm_permissions_sort_name_idx',
            'columns' => ['sort_order', 'name'],
        ],
        [
            'table' => 'crm_user_sites',
            'name' => 'crm_user_sites_user_default_idx',
            'columns' => ['user_id', 'is_default'],
        ],
        [
            'table' => 'crm_user_site_module_permissions',
            'name' => 'crm_usmp_user_module_permission_site_idx',
            'columns' => ['user_id', 'module_id', 'permission_id', 'site_id'],
        ],
        [
            'table' => 'crm_reservations',
            'name' => 'crm_reservations_vehicle_end_start_idx',
            'columns' => ['vehicle_id', 'end_at', 'start_at'],
        ],
        [
            'table' => 'crm_reservations',
            'name' => 'crm_reservations_site_end_start_idx',
            'columns' => ['site_id', 'end_at', 'start_at'],
        ],
        [
            'table' => 'crm_equipment_rentals',
            'name' => 'crm_equipment_rentals_item_end_start_status_idx',
            'columns' => ['equipment_item_id', 'end_at', 'start_at', 'status'],
        ],
        [
            'table' => 'crm_equipment_rentals',
            'name' => 'crm_equipment_rentals_site_status_end_start_idx',
            'columns' => ['site_id', 'status', 'end_at', 'start_at'],
        ],
        [
            'table' => 'crm_cash_receipts',
            'name' => 'crm_cash_receipts_occurred_day_idx',
            'columns' => ['occurred_on', 'cash_register_day_id'],
        ],
        [
            'table' => 'crm_check_remittances',
            'name' => 'crm_check_remittances_site_status_idx',
            'columns' => ['site_id', 'status'],
        ],
        [
            'table' => 'notification_logs',
            'name' => 'notification_logs_channel_template_created_idx',
            'columns' => ['channel', 'template_key', 'created_at'],
        ],
        [
            'table' => 'crm_logs',
            'name' => 'crm_logs_action_created_idx',
            'columns' => ['action', 'created_at'],
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
