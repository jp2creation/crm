<?php

namespace Tests\Feature;

use App\Models\CrmEquipmentCategory;
use App\Models\CrmEquipmentItem;
use App\Models\CrmEquipmentRental;
use App\Models\CrmLeaveEmployee;
use App\Models\CrmLeaveEntry;
use App\Models\CrmModule;
use App\Models\CrmPermission;
use App\Models\CrmReservation;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\CrmVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CrmPolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_policy_uses_site_permissions_and_ownership(): void
    {
        [$account, $crmUser, $site] = $this->createCrmActor('reservations', [
            'reservations.view',
            'reservations.create',
            'reservations.update_own',
            'reservations.delete_own',
        ]);

        $vehicle = CrmVehicle::query()->create([
            'site_id' => $site->id,
            'name' => 'Sprinter Policy',
            'description' => 'Vehicule test',
            'color' => '#95002e',
            'active' => true,
        ]);

        $ownReservation = CrmReservation::query()->create([
            'site_id' => $site->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $crmUser->id,
            'user_name' => $crmUser->name,
            'title' => 'A moi',
            'start_at' => '2026-09-01 08:00:00',
            'end_at' => '2026-09-01 09:00:00',
            'notes' => '',
        ]);
        $otherReservation = CrmReservation::query()->create([
            'site_id' => $site->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $this->createBareCrmUser()->id,
            'user_name' => 'Autre',
            'title' => 'Autre',
            'start_at' => '2026-09-01 09:30:00',
            'end_at' => '2026-09-01 10:30:00',
            'notes' => '',
        ]);

        $gate = Gate::forUser($account);

        $this->assertTrue($gate->allows('viewAny', CrmReservation::class));
        $this->assertTrue($gate->allows('view', $ownReservation));
        $this->assertTrue($gate->allows('create', CrmReservation::class));
        $this->assertTrue($gate->allows('createForSite', [CrmReservation::class, $site->id]));
        $this->assertTrue($gate->allows('update', $ownReservation));
        $this->assertTrue($gate->allows('delete', $ownReservation));
        $this->assertFalse($gate->allows('update', $otherReservation));
        $this->assertFalse($gate->allows('delete', $otherReservation));
    }

    public function test_equipment_rental_policy_uses_site_permissions_and_ownership(): void
    {
        [$account, $crmUser, $site] = $this->createCrmActor('locations-materiel', [
            'equipment_rentals.view',
            'equipment_rentals.create',
            'equipment_rentals.update_own',
            'equipment_rentals.delete_own',
        ]);

        $category = CrmEquipmentCategory::query()->create([
            'name' => 'Poncage Policy',
            'slug' => 'poncage-policy',
            'active' => true,
            'sort_order' => 10,
        ]);
        $item = CrmEquipmentItem::query()->create([
            'site_id' => $site->id,
            'category_id' => $category->id,
            'name' => 'Ponceuse Policy',
            'inventory_code' => 'POL-001',
            'description' => 'Materiel test',
            'color' => '#95002e',
            'half_day_price' => 45,
            'day_price' => 80,
            'deposit_amount' => 300,
            'active' => true,
            'sort_order' => 10,
        ]);

        $ownRental = CrmEquipmentRental::query()->create([
            'site_id' => $site->id,
            'equipment_item_id' => $item->id,
            'user_id' => $crmUser->id,
            'user_name' => $crmUser->name,
            'period_type' => 'half_day',
            'slot' => 'morning',
            'status' => CrmEquipmentRental::STATUS_RESERVED,
            'title' => 'A moi',
            'start_at' => '2026-09-02 08:00:00',
            'end_at' => '2026-09-02 09:00:00',
            'notes' => '',
        ]);
        $otherRental = CrmEquipmentRental::query()->create([
            'site_id' => $site->id,
            'equipment_item_id' => $item->id,
            'user_id' => $this->createBareCrmUser()->id,
            'user_name' => 'Autre',
            'period_type' => 'half_day',
            'slot' => 'morning',
            'status' => CrmEquipmentRental::STATUS_RESERVED,
            'title' => 'Autre',
            'start_at' => '2026-09-02 09:30:00',
            'end_at' => '2026-09-02 10:30:00',
            'notes' => '',
        ]);

        $gate = Gate::forUser($account);

        $this->assertTrue($gate->allows('viewAny', CrmEquipmentRental::class));
        $this->assertTrue($gate->allows('view', $ownRental));
        $this->assertTrue($gate->allows('create', CrmEquipmentRental::class));
        $this->assertTrue($gate->allows('createForSite', [CrmEquipmentRental::class, $site->id]));
        $this->assertTrue($gate->allows('update', $ownRental));
        $this->assertTrue($gate->allows('delete', $ownRental));
        $this->assertFalse($gate->allows('update', $otherRental));
        $this->assertFalse($gate->allows('delete', $otherRental));
    }

    public function test_vehicle_policy_requires_manage_permission_for_mutations(): void
    {
        [$viewerAccount, , $site] = $this->createCrmActor('reservations', ['reservations.view']);
        [$managerAccount] = $this->createCrmActor('reservations', ['reservations.view', 'reservations.manage_vehicles'], $site);

        $vehicle = CrmVehicle::query()->create([
            'site_id' => $site->id,
            'name' => 'Vehicule Policy',
            'description' => 'Vehicule test',
            'color' => '#95002e',
            'active' => true,
        ]);

        $viewerGate = Gate::forUser($viewerAccount);
        $managerGate = Gate::forUser($managerAccount);

        $this->assertTrue($viewerGate->allows('view', $vehicle));
        $this->assertFalse($viewerGate->allows('createForSite', [CrmVehicle::class, $site->id]));
        $this->assertFalse($viewerGate->allows('update', $vehicle));
        $this->assertFalse($viewerGate->allows('delete', $vehicle));

        $this->assertTrue($managerGate->allows('createForSite', [CrmVehicle::class, $site->id]));
        $this->assertTrue($managerGate->allows('update', $vehicle));
        $this->assertTrue($managerGate->allows('delete', $vehicle));
    }

    public function test_equipment_item_policy_requires_manage_permission_for_mutations(): void
    {
        [$viewerAccount, , $site] = $this->createCrmActor('locations-materiel', ['equipment_rentals.view']);
        [$managerAccount] = $this->createCrmActor('locations-materiel', ['equipment_rentals.view', 'equipment_rentals.manage_items'], $site);

        $category = CrmEquipmentCategory::query()->create([
            'name' => 'Materiel Policy',
            'slug' => 'materiel-policy',
            'active' => true,
            'sort_order' => 10,
        ]);
        $item = CrmEquipmentItem::query()->create([
            'site_id' => $site->id,
            'category_id' => $category->id,
            'name' => 'Materiel Policy',
            'inventory_code' => 'ITEM-POLICY',
            'description' => 'Materiel test',
            'color' => '#95002e',
            'half_day_price' => 45,
            'day_price' => 80,
            'deposit_amount' => 300,
            'active' => true,
            'sort_order' => 10,
        ]);

        $viewerGate = Gate::forUser($viewerAccount);
        $managerGate = Gate::forUser($managerAccount);

        $this->assertTrue($viewerGate->allows('view', $item));
        $this->assertFalse($viewerGate->allows('createForSite', [CrmEquipmentItem::class, $site->id]));
        $this->assertFalse($viewerGate->allows('update', $item));
        $this->assertFalse($viewerGate->allows('delete', $item));

        $this->assertTrue($managerGate->allows('createForSite', [CrmEquipmentItem::class, $site->id]));
        $this->assertTrue($managerGate->allows('update', $item));
        $this->assertTrue($managerGate->allows('delete', $item));
    }

    public function test_leave_policy_requires_manage_permission_for_mutations(): void
    {
        [$viewerAccount, $viewerCrmUser, $site] = $this->createCrmActor('conges', ['conges.view']);
        [$managerAccount] = $this->createCrmActor('conges', ['conges.view', 'conges.manage'], $site);

        $employee = CrmLeaveEmployee::query()->create([
            'crm_user_id' => $viewerCrmUser->id,
            'name' => $viewerCrmUser->name,
            'slug' => 'policy-leave-user',
            'color' => '#2563eb',
            'active' => true,
            'sort_order' => 10,
        ]);
        $entry = CrmLeaveEntry::query()->create([
            'employee_id' => $employee->id,
            'start_date' => '2026-09-03',
            'end_date' => '2026-09-03',
            'type' => 'paid',
            'period' => 'full_day',
            'duration_days' => 1,
            'status' => 'approved',
            'notes' => '',
            'source' => 'test',
            'created_by' => $viewerCrmUser->id,
            'updated_by' => $viewerCrmUser->id,
        ]);

        $viewerGate = Gate::forUser($viewerAccount);
        $managerGate = Gate::forUser($managerAccount);

        $this->assertTrue($viewerGate->allows('viewAny', CrmLeaveEntry::class));
        $this->assertTrue($viewerGate->allows('view', $entry));
        $this->assertFalse($viewerGate->allows('create', CrmLeaveEntry::class));
        $this->assertFalse($viewerGate->allows('update', $entry));
        $this->assertFalse($viewerGate->allows('delete', $entry));

        $this->assertTrue($managerGate->allows('createForSite', [CrmLeaveEntry::class, $site->id]));
        $this->assertTrue($managerGate->allows('update', $entry));
        $this->assertTrue($managerGate->allows('delete', $entry));
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array{0: User, 1: CrmUser, 2: CrmSite}
     */
    private function createCrmActor(string $moduleSlug, array $permissions, ?CrmSite $site = null): array
    {
        $account = User::factory()->create();
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'Policy User '.$account->id,
            'role' => 'user',
            'active' => true,
        ]);
        $site ??= $this->createSite('Policy Palissy '.$account->id);

        $module = CrmModule::query()->updateOrCreate(
            ['slug' => $moduleSlug],
            [
                'name' => $moduleSlug,
                'description' => 'Module policy',
                'route_path' => '/'.$moduleSlug,
                'active' => true,
                'sort_order' => 10,
            ],
        );

        $permissionIds = [];
        foreach ($permissions as $index => $permission) {
            $permissionIds[] = CrmPermission::query()->updateOrCreate(
                ['name' => $permission],
                [
                    'label' => $permission,
                    'group_label' => 'Policy',
                    'sort_order' => 100 + $index,
                ],
            )->id;
        }

        $crmUser->sites()->syncWithoutDetaching([$site->id => ['is_default' => true]]);
        $crmUser->modules()->syncWithoutDetaching([$module->id]);
        $crmUser->permissions()->syncWithoutDetaching($permissionIds);

        return [$account, $crmUser, $site];
    }

    private function createBareCrmUser(): CrmUser
    {
        return CrmUser::query()->create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Bare Policy User',
            'role' => 'user',
            'active' => true,
        ]);
    }

    private function createSite(string $name): CrmSite
    {
        return CrmSite::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'active' => true,
            'morning_start' => '07:30:00',
            'morning_end' => '12:00:00',
            'afternoon_start' => '13:30:00',
            'afternoon_end' => '17:30:00',
        ]);
    }
}
