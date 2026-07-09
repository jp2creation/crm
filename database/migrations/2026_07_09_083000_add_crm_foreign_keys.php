<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, array{table: string, constraint: string, columns: array<int, string>, references: array<int, string>, on: string, on_delete?: string}>
     */
    private array $foreignKeys = [
        [
            'table' => 'crm_vehicles',
            'constraint' => 'crm_vehicles_site_id_fk',
            'columns' => ['site_id'],
            'references' => ['id'],
            'on' => 'crm_sites',
        ],
        [
            'table' => 'crm_reservations',
            'constraint' => 'crm_reservations_site_id_fk',
            'columns' => ['site_id'],
            'references' => ['id'],
            'on' => 'crm_sites',
        ],
        [
            'table' => 'crm_reservations',
            'constraint' => 'crm_reservations_vehicle_id_fk',
            'columns' => ['vehicle_id'],
            'references' => ['id'],
            'on' => 'crm_vehicles',
        ],
        [
            'table' => 'crm_reservations',
            'constraint' => 'crm_reservations_user_id_fk',
            'columns' => ['user_id'],
            'references' => ['id'],
            'on' => 'crm_users',
        ],
        [
            'table' => 'crm_equipment_items',
            'constraint' => 'crm_equipment_items_site_id_fk',
            'columns' => ['site_id'],
            'references' => ['id'],
            'on' => 'crm_sites',
        ],
        [
            'table' => 'crm_equipment_items',
            'constraint' => 'crm_equipment_items_category_id_fk',
            'columns' => ['category_id'],
            'references' => ['id'],
            'on' => 'crm_equipment_categories',
        ],
        [
            'table' => 'crm_equipment_rentals',
            'constraint' => 'crm_equipment_rentals_site_id_fk',
            'columns' => ['site_id'],
            'references' => ['id'],
            'on' => 'crm_sites',
        ],
        [
            'table' => 'crm_equipment_rentals',
            'constraint' => 'crm_equipment_rentals_item_id_fk',
            'columns' => ['equipment_item_id'],
            'references' => ['id'],
            'on' => 'crm_equipment_items',
        ],
        [
            'table' => 'crm_equipment_rentals',
            'constraint' => 'crm_equipment_rentals_user_id_fk',
            'columns' => ['user_id'],
            'references' => ['id'],
            'on' => 'crm_users',
        ],
        [
            'table' => 'crm_leave_employees',
            'constraint' => 'crm_leave_employees_crm_user_id_fk',
            'columns' => ['crm_user_id'],
            'references' => ['id'],
            'on' => 'crm_users',
            'on_delete' => 'SET NULL',
        ],
        [
            'table' => 'crm_leave_entries',
            'constraint' => 'crm_leave_entries_employee_id_fk',
            'columns' => ['employee_id'],
            'references' => ['id'],
            'on' => 'crm_leave_employees',
        ],
        [
            'table' => 'crm_leave_entries',
            'constraint' => 'crm_leave_entries_created_by_fk',
            'columns' => ['created_by'],
            'references' => ['id'],
            'on' => 'crm_users',
            'on_delete' => 'SET NULL',
        ],
        [
            'table' => 'crm_leave_entries',
            'constraint' => 'crm_leave_entries_updated_by_fk',
            'columns' => ['updated_by'],
            'references' => ['id'],
            'on' => 'crm_users',
            'on_delete' => 'SET NULL',
        ],
        [
            'table' => 'crm_user_sites',
            'constraint' => 'crm_user_sites_site_id_fk',
            'columns' => ['site_id'],
            'references' => ['id'],
            'on' => 'crm_sites',
            'on_delete' => 'CASCADE',
        ],
        [
            'table' => 'crm_user_sites',
            'constraint' => 'crm_user_sites_user_id_fk',
            'columns' => ['user_id'],
            'references' => ['id'],
            'on' => 'crm_users',
            'on_delete' => 'CASCADE',
        ],
        [
            'table' => 'crm_user_modules',
            'constraint' => 'crm_user_modules_module_id_fk',
            'columns' => ['module_id'],
            'references' => ['id'],
            'on' => 'crm_modules',
            'on_delete' => 'CASCADE',
        ],
        [
            'table' => 'crm_user_modules',
            'constraint' => 'crm_user_modules_user_id_fk',
            'columns' => ['user_id'],
            'references' => ['id'],
            'on' => 'crm_users',
            'on_delete' => 'CASCADE',
        ],
        [
            'table' => 'crm_user_permissions',
            'constraint' => 'crm_user_permissions_permission_id_fk',
            'columns' => ['permission_id'],
            'references' => ['id'],
            'on' => 'crm_permissions',
            'on_delete' => 'CASCADE',
        ],
        [
            'table' => 'crm_user_permissions',
            'constraint' => 'crm_user_permissions_user_id_fk',
            'columns' => ['user_id'],
            'references' => ['id'],
            'on' => 'crm_users',
            'on_delete' => 'CASCADE',
        ],
        [
            'table' => 'crm_menu_items',
            'constraint' => 'crm_menu_items_group_key_fk',
            'columns' => ['group_key'],
            'references' => ['menu_key'],
            'on' => 'crm_menu_groups',
        ],
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->foreignKeys as $foreignKey) {
            $this->addForeignKey($foreignKey);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (array_reverse($this->foreignKeys) as $foreignKey) {
            if ($this->foreignKeyExists($foreignKey['table'], $foreignKey['constraint'])) {
                DB::statement(sprintf(
                    'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                    $foreignKey['table'],
                    $foreignKey['constraint'],
                ));
            }
        }
    }

    /**
     * @param  array{table: string, constraint: string, columns: array<int, string>, references: array<int, string>, on: string, on_delete?: string}  $foreignKey
     */
    private function addForeignKey(array $foreignKey): void
    {
        if (
            ! Schema::hasTable($foreignKey['table'])
            || ! Schema::hasTable($foreignKey['on'])
            || $this->foreignKeyExists($foreignKey['table'], $foreignKey['constraint'])
        ) {
            return;
        }

        foreach ($foreignKey['columns'] as $column) {
            if (! Schema::hasColumn($foreignKey['table'], $column)) {
                return;
            }
        }

        foreach ($foreignKey['references'] as $column) {
            if (! Schema::hasColumn($foreignKey['on'], $column)) {
                return;
            }
        }

        $this->addIndexIfMissing($foreignKey['table'], $foreignKey['constraint'], $foreignKey['columns']);

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (%s) REFERENCES `%s` (%s) ON DELETE %s ON UPDATE RESTRICT',
            $foreignKey['table'],
            $foreignKey['constraint'],
            $this->columnsSql($foreignKey['columns']),
            $foreignKey['on'],
            $this->columnsSql($foreignKey['references']),
            $foreignKey['on_delete'] ?? 'RESTRICT',
        ));
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addIndexIfMissing(string $table, string $index, array $columns): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
            $table,
            $index,
            $this->columnsSql($columns),
        ));
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function columnsSql(array $columns): string
    {
        return collect($columns)
            ->map(fn (string $column): string => "`{$column}`")
            ->implode(', ');
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

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }
};
