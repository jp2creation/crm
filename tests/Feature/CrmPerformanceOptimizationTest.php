<?php

namespace Tests\Feature;

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

    public function test_crm_query_indexes_are_available(): void
    {
        $expectedIndexes = [
            'crm_sites' => 'crm_sites_active_name_idx',
            'crm_modules' => 'crm_modules_active_sort_name_idx',
            'crm_user_site_module_permissions' => 'crm_usmp_user_module_permission_site_idx',
            'crm_reservations' => 'crm_reservations_vehicle_end_start_idx',
            'crm_equipment_rentals' => 'crm_equipment_rentals_site_status_end_start_idx',
            'crm_cash_receipts' => 'crm_cash_receipts_occurred_day_idx',
            'notification_logs' => 'notification_logs_channel_template_created_idx',
        ];

        foreach ($expectedIndexes as $table => $indexName) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $this->assertTrue(
                collect(Schema::getIndexes($table))
                    ->contains(fn (array $index): bool => strcasecmp((string) ($index['name'] ?? ''), $indexName) === 0),
                "{$indexName} doit exister sur {$table}.",
            );
        }
    }
}
