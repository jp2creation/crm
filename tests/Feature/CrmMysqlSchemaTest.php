<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('mysql-critical')]
class CrmMysqlSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_mysql_specific_migrations_create_foreign_keys_and_innodb_tables(): void
    {
        $this->skipUnlessMysql();

        foreach (['users', 'crm_users', 'crm_pages'] as $table) {
            $this->assertSame('InnoDB', $this->tableEngine($table));
        }

        foreach ([
            ['crm_users', 'crm_users_user_id_foreign', 'users', 'SET NULL'],
            ['crm_reservations', 'crm_reservations_site_id_fk', 'crm_sites', 'RESTRICT'],
            ['crm_reservations', 'crm_reservations_vehicle_id_fk', 'crm_vehicles', 'RESTRICT'],
            ['crm_reservations', 'crm_reservations_user_id_fk', 'crm_users', 'RESTRICT'],
            ['crm_equipment_rentals', 'crm_equipment_rentals_item_id_fk', 'crm_equipment_items', 'RESTRICT'],
            ['crm_leave_entries', 'crm_leave_entries_employee_id_fk', 'crm_leave_employees', 'RESTRICT'],
            ['crm_user_sites', 'crm_user_sites_user_id_fk', 'crm_users', 'CASCADE'],
            ['crm_menu_items', 'crm_menu_items_group_key_fk', 'crm_menu_groups', 'RESTRICT'],
        ] as [$table, $constraint, $referencedTable, $deleteRule]) {
            $this->assertForeignKeyExists($table, $constraint, $referencedTable, $deleteRule);
        }
    }

    public function test_mysql_foreign_keys_reject_orphaned_business_rows(): void
    {
        $this->skipUnlessMysql();

        try {
            DB::table('crm_reservations')->insert([
                'site_id' => 999_001,
                'vehicle_id' => 999_002,
                'user_id' => 999_003,
                'user_name' => 'Orphan',
                'title' => 'Reservation impossible',
                'contact_phone' => '',
                'start_at' => '2026-08-03 08:00:00',
                'end_at' => '2026-08-03 09:00:00',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->fail('MariaDB should reject orphaned reservations through foreign keys.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('foreign key', strtolower($exception->getMessage()));
        }
    }

    public function test_mysql_cascade_constraints_are_effective(): void
    {
        $this->skipUnlessMysql();

        $siteId = DB::table('crm_sites')->insertGetId([
            'name' => 'Site FK',
            'slug' => 'site-fk',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $userId = DB::table('crm_users')->insertGetId([
            'name' => 'User FK',
            'role' => 'user',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('crm_user_sites')->insert([
            'site_id' => $siteId,
            'user_id' => $userId,
            'is_default' => true,
            'created_at' => now(),
        ]);

        DB::table('crm_users')->where('id', $userId)->delete();

        $this->assertDatabaseMissing('crm_user_sites', ['user_id' => $userId]);
    }

    private function skipUnlessMysql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL/MariaDB schema assertions are only relevant on the mysql driver.');
        }
    }

    private function tableEngine(string $table): ?string
    {
        return DB::table('information_schema.TABLES')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->value('ENGINE');
    }

    private function assertForeignKeyExists(
        string $table,
        string $constraint,
        string $referencedTable,
        string $deleteRule,
    ): void {
        $exists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('REFERENCED_TABLE_NAME', $referencedTable)
            ->where('DELETE_RULE', $deleteRule)
            ->exists();

        $this->assertTrue($exists, "Missing foreign key {$constraint} on {$table}.");
    }
}
