<?php

namespace Tests\Feature;

use App\Models\CrmEquipmentCategory;
use App\Models\CrmEquipmentItem;
use App\Models\CrmModule;
use App\Models\CrmPermission;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\CrmVehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CrmCore\Services\CrmAccessService;
use Modules\CrmCore\Support\CrmReferenceCache;
use Tests\TestCase;

class CrmPerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_rows_are_cached_and_invalidated(): void
    {
        Cache::flush();

        $site = CrmSite::query()->create([
            'name' => 'Palissy',
            'slug' => 'palissy',
            'active' => true,
        ]);
        $vehicle = CrmVehicle::query()->create([
            'site_id' => $site->id,
            'name' => 'Sprinter cache',
            'description' => '',
            'active' => true,
        ]);

        DB::enableQueryLog();
        CrmReferenceCache::activeVehicleRows();
        $queriesAfterWarmup = count(DB::getQueryLog());

        CrmReferenceCache::activeVehicleRows();
        $this->assertSame($queriesAfterWarmup, count(DB::getQueryLog()));

        $vehicle->update(['name' => 'Sprinter cache modifie']);

        $this->assertSame(
            'Sprinter cache modifie',
            collect(CrmReferenceCache::activeVehicleRows())->firstWhere('id', $vehicle->id)['name'] ?? null,
        );
    }

    public function test_access_reference_lookups_can_be_served_from_cache(): void
    {
        Cache::flush();

        $site = CrmSite::query()->create([
            'name' => 'Palissy',
            'slug' => 'palissy',
            'active' => true,
        ]);
        CrmModule::query()->create([
            'name' => 'Reservations',
            'slug' => 'reservations',
            'description' => '',
            'route_path' => '/reservations',
            'active' => true,
            'sort_order' => 10,
        ]);
        CrmPermission::query()->create([
            'name' => 'reservations.view',
            'label' => 'Voir les reservations',
            'group_label' => 'Reservations',
            'sort_order' => 10,
        ]);
        $actor = CrmUser::query()->create([
            'name' => 'Admin cache',
            'role' => 'admin',
            'active' => true,
        ]);

        CrmReferenceCache::activeModuleLookup();
        CrmReferenceCache::permissionIdLookup();
        CrmReferenceCache::activeSiteIds();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->assertTrue(app(CrmAccessService::class)->canOnSite($actor, $site->id, 'reservations', 'reservations.view'));
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_equipment_reference_rows_are_cached_and_invalidated(): void
    {
        Cache::flush();

        $site = CrmSite::query()->create([
            'name' => 'Palissy',
            'slug' => 'palissy',
            'active' => true,
        ]);
        $category = CrmEquipmentCategory::query()->create([
            'name' => 'Ponceuses',
            'slug' => 'ponceuses',
            'active' => true,
            'sort_order' => 10,
        ]);
        $item = CrmEquipmentItem::query()->create([
            'site_id' => $site->id,
            'category_id' => $category->id,
            'name' => 'Ponceuse cache',
            'inventory_code' => 'PON-1',
            'description' => '',
            'color' => '#95002e',
            'half_day_price' => 40,
            'day_price' => 80,
            'show_day_price' => true,
            'rental_mode' => 'half_day_and_day',
            'deposit_amount' => 0,
            'active' => true,
            'sort_order' => 10,
        ]);

        CrmReferenceCache::activeEquipmentCategoryRows();
        CrmReferenceCache::activeEquipmentItemRows();

        $category->update(['name' => 'Ponceuses parquet']);
        $item->update(['name' => 'Ponceuse cache modifiee']);

        $this->assertSame(
            'Ponceuses parquet',
            collect(CrmReferenceCache::activeEquipmentCategoryRows())->firstWhere('id', $category->id)['name'] ?? null,
        );
        $this->assertSame(
            'Ponceuse cache modifiee',
            collect(CrmReferenceCache::activeEquipmentItemRows())->firstWhere('id', $item->id)['name'] ?? null,
        );
    }

    public function test_crm_query_indexes_are_available(): void
    {
        $expectedIndexes = [
            'crm_sites' => [
                'crm_sites_active_name_idx' => ['active', 'name'],
            ],
            'crm_modules' => [
                'crm_modules_active_sort_name_idx' => ['active', 'sort_order', 'name'],
            ],
            'crm_user_site_module_permissions' => [
                'crm_usmp_user_module_permission_site_idx' => ['user_id', 'module_id', 'permission_id', 'site_id'],
            ],
            'crm_reservations' => [
                'crm_reservations_vehicle_end_start_idx' => ['vehicle_id', 'end_at', 'start_at'],
                'crm_reservations_site_end_start_idx' => ['site_id', 'end_at', 'start_at'],
            ],
            'crm_equipment_rentals' => [
                'crm_equipment_rentals_item_end_start_status_idx' => ['equipment_item_id', 'end_at', 'start_at', 'status'],
                'crm_equipment_rentals_site_status_end_start_idx' => ['site_id', 'status', 'end_at', 'start_at'],
            ],
            'crm_equipment_items' => [
                'crm_equipment_items_active_site_sort_idx' => ['active', 'site_id', 'sort_order', 'name'],
            ],
            'crm_cash_receipts' => [
                'crm_cash_receipts_occurred_day_idx' => ['occurred_on', 'cash_register_day_id'],
            ],
            'notification_logs' => [
                'notification_logs_channel_template_created_idx' => ['channel', 'template_key', 'created_at'],
            ],
        ];

        foreach ($expectedIndexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $indexName => $columns) {
                $this->assertIndexExists($table, $indexName, $columns);
            }
        }
    }

    /**
     * @param  array<int, string>  $expectedColumns
     */
    private function assertIndexExists(string $table, string $indexName, array $expectedColumns): void
    {
        $index = collect(Schema::getIndexes($table))
            ->first(fn (array $index): bool => strcasecmp((string) ($index['name'] ?? ''), $indexName) === 0);

        $this->assertNotNull($index, "{$indexName} doit exister sur {$table}.");
        $this->assertSame(
            $expectedColumns,
            array_values(array_map('strval', $index['columns'] ?? [])),
            "{$indexName} doit couvrir les colonnes attendues sur {$table}.",
        );
    }
}
