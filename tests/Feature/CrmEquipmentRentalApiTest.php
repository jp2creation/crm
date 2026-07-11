<?php

namespace Tests\Feature;

use App\Models\CrmEquipmentCategory;
use App\Models\CrmEquipmentItem;
use App\Models\CrmEquipmentRental;
use App\Models\CrmModule;
use App\Models\CrmPermission;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmEquipmentRentalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_does_not_require_authentication(): void
    {
        $this->getJson('/api/equipment-rentals?action=health')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('mode', 'mysql');
    }

    public function test_guest_cannot_read_equipment_bootstrap(): void
    {
        $this->getJson('/api/equipment-rentals?action=bootstrap')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Utilisateur CRM requis');
    }

    public function test_authorized_user_can_read_equipment_bootstrap(): void
    {
        [$account, $crmUser, $site, $item] = $this->createCrmUser(['equipment_rentals.view']);

        CrmEquipmentRental::query()->create([
            'site_id' => $site->id,
            'equipment_item_id' => $item->id,
            'user_id' => $crmUser->id,
            'user_name' => $crmUser->name,
            'period_type' => 'half_day',
            'slot' => 'morning',
            'status' => 'reserved',
            'title' => 'Location test',
            'contact_phone' => '',
            'start_at' => '2026-08-03 08:00:00',
            'end_at' => '2026-08-03 09:00:00',
            'notes' => '',
        ]);

        $this->actingAs($account)
            ->getJson('/api/equipment-rentals?action=bootstrap')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('equipmentItems.0.name', 'Ponceuse Test')
            ->assertJsonPath('equipmentItems.0.showDayPrice', true)
            ->assertJsonPath('equipmentItems.0.rentalMode', 'half_day_and_day')
            ->assertJsonPath('equipmentRentals.0.title', 'Location test')
            ->assertJsonPath('user.siteIds.0', $site->id);
    }

    public function test_create_permission_is_required_to_create_rental(): void
    {
        [$account, , , $item] = $this->createCrmUser(['equipment_rentals.view']);

        $this->actingAs($account)
            ->postJson('/api/equipment-rentals?action=create_rental', [
                'equipmentItemId' => $item->id,
                'date' => '2026-08-04',
                'slot' => 'morning',
                'title' => 'Sans droit',
            ])
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Droit insuffisant : equipment_rentals.create');
    }

    public function test_overlapping_rental_is_rejected(): void
    {
        [$account, , , $item] = $this->createCrmUser(['equipment_rentals.create']);

        $payload = [
            'equipmentItemId' => $item->id,
            'startAt' => '2026-08-05T08:00',
            'endAt' => '2026-08-05T10:00',
            'title' => 'Premiere location',
        ];

        $this->actingAs($account)
            ->postJson('/api/equipment-rentals?action=create_rental', $payload)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('equipmentRental.title', 'Premiere location');

        $this->actingAs($account)
            ->postJson('/api/equipment-rentals?action=create_rental', [
                ...$payload,
                'startAt' => '2026-08-05T09:30',
                'endAt' => '2026-08-05T11:00',
            ])
            ->assertStatus(409)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Ce materiel est deja loue sur ce creneau');
    }

    public function test_user_can_update_own_rental(): void
    {
        [$account, , , $item] = $this->createCrmUser(['equipment_rentals.create', 'equipment_rentals.update_own']);

        $rentalId = $this->actingAs($account)
            ->postJson('/api/equipment-rentals.php?action=create_rental', [
                'equipmentItemId' => $item->id,
                'startAt' => '2026-08-06T08:00',
                'endAt' => '2026-08-06T09:00',
                'title' => 'A modifier',
            ])
            ->assertOk()
            ->json('equipmentRental.id');

        $this->actingAs($account)
            ->postJson('/api/equipment-rentals?action=update_rental', [
                'id' => $rentalId,
                'equipmentItemId' => $item->id,
                'startAt' => '2026-08-06T09:00',
                'endAt' => '2026-08-06T10:00',
                'title' => 'Modifie',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('equipmentRental.title', 'Modifie')
            ->assertJsonPath('equipmentRental.startAt', '2026-08-06T09:00');
    }

    public function test_manager_can_save_and_hide_equipment_item(): void
    {
        [$account, , $site] = $this->createCrmUser(['equipment_rentals.manage_items']);

        $itemId = $this->actingAs($account)
            ->postJson('/api/equipment-rentals?action=save_equipment_item', [
                'siteId' => $site->id,
                'categoryName' => 'Outillage',
                'name' => 'Scie test',
                'inventoryCode' => 'SCIE-TEST',
                'description' => 'Materiel atelier',
                'color' => '#123abc',
                'halfDayPrice' => '12,5',
                'dayPrice' => '20',
                'showDayPrice' => false,
                'rentalMode' => 'day_only',
                'depositAmount' => '100',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('equipmentItem.name', 'Scie test')
            ->assertJsonPath('equipmentItem.showDayPrice', false)
            ->assertJsonPath('equipmentItem.rentalMode', 'day_only')
            ->assertJsonPath('equipmentCategory.slug', 'outillage')
            ->json('equipmentItem.id');

        $this->actingAs($account)
            ->postJson('/api/equipment-rentals?action=delete_equipment_item', ['id' => $itemId])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('id', $itemId);

        $this->assertDatabaseHas('crm_equipment_items', [
            'id' => $itemId,
            'show_day_price' => false,
            'rental_mode' => 'day_only',
            'active' => false,
        ]);
    }

    public function test_day_only_equipment_rejects_half_day_rental(): void
    {
        [$account, , , $item] = $this->createCrmUser(['equipment_rentals.create']);
        $item->forceFill(['rental_mode' => 'day_only'])->save();

        $this->actingAs($account)
            ->postJson('/api/equipment-rentals?action=create_rental', [
                'equipmentItemId' => $item->id,
                'date' => '2026-08-07',
                'slot' => 'morning',
                'title' => 'Demi journee refusee',
            ])
            ->assertStatus(400)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Ce materiel se loue uniquement a la journee');

        $this->actingAs($account)
            ->postJson('/api/equipment-rentals?action=create_rental', [
                'equipmentItemId' => $item->id,
                'date' => '2026-08-07',
                'slot' => 'full_day',
                'periodType' => 'day',
                'title' => 'Journee acceptee',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('equipmentRental.slot', 'full_day');
    }

    public function test_day_only_equipment_rejects_custom_partial_day_times(): void
    {
        [$account, , , $item] = $this->createCrmUser(['equipment_rentals.create']);
        $item->forceFill(['rental_mode' => 'day_only'])->save();

        $this->actingAs($account)
            ->postJson('/api/equipment-rentals?action=create_rental', [
                'equipmentItemId' => $item->id,
                'periodType' => 'day',
                'slot' => 'full_day',
                'startAt' => '2026-08-08T07:30',
                'endAt' => '2026-08-08T12:00',
                'title' => 'Fausse journee',
            ])
            ->assertStatus(400)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Ce materiel se loue uniquement a la journee');
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array{0: User, 1: CrmUser, 2: CrmSite, 3: CrmEquipmentItem}
     */
    private function createCrmUser(array $permissions): array
    {
        $account = User::factory()->create();
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'CRM Equipment User '.$account->id,
            'role' => 'user',
            'active' => true,
        ]);

        $site = CrmSite::query()->create([
            'name' => 'Palissy Test',
            'slug' => 'palissy-test',
            'active' => true,
            'morning_start' => '07:30:00',
            'morning_end' => '12:00:00',
            'afternoon_start' => '13:30:00',
            'afternoon_end' => '17:30:00',
        ]);

        $category = CrmEquipmentCategory::query()->create([
            'name' => 'Poncage Test',
            'slug' => 'poncage-test',
            'active' => true,
            'sort_order' => 10,
        ]);

        $item = CrmEquipmentItem::query()->create([
            'site_id' => $site->id,
            'category_id' => $category->id,
            'name' => 'Ponceuse Test',
            'inventory_code' => 'PON-TEST',
            'description' => 'Materiel test',
            'color' => '#95002e',
            'half_day_price' => 45,
            'day_price' => 80,
            'deposit_amount' => 300,
            'active' => true,
            'sort_order' => 10,
        ]);

        $module = CrmModule::query()->updateOrCreate(
            ['slug' => 'locations-materiel'],
            [
                'name' => 'Location materiel',
                'description' => 'Planning et locations du materiel interne',
                'route_path' => '/locations-materiel',
                'active' => true,
                'sort_order' => 15,
            ],
        );

        $permissionIds = [];

        foreach ($permissions as $index => $permission) {
            $permissionIds[] = CrmPermission::query()->updateOrCreate(
                ['name' => $permission],
                [
                    'label' => $permission,
                    'group_label' => 'Location materiel',
                    'sort_order' => 120 + $index,
                ],
            )->id;
        }

        $crmUser->sites()->syncWithoutDetaching([$site->id => ['is_default' => true]]);
        $crmUser->modules()->syncWithoutDetaching([$module->id]);
        $crmUser->permissions()->syncWithoutDetaching($permissionIds);

        return [$account, $crmUser, $site, $item];
    }
}
