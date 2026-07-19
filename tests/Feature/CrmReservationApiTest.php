<?php

namespace Tests\Feature;

use App\Models\CrmModule;
use App\Models\CrmPermission;
use App\Models\CrmReservation;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\CrmVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmReservationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_does_not_require_authentication(): void
    {
        $this->getJson('/api/reservations?action=health')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('mode', 'mysql');
    }

    public function test_guest_cannot_read_reservation_bootstrap(): void
    {
        $this->getJson('/api/reservations?action=bootstrap')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Utilisateur CRM requis');
    }

    public function test_invalid_reservation_query_parameters_return_validation_error(): void
    {
        $this->getJson('/api/reservations?action=bootstrap&siteId=invalide')
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Site invalide.');
    }

    public function test_authorized_user_can_read_reservation_bootstrap(): void
    {
        [$account, $crmUser, $site, $vehicle] = $this->createCrmUser(['reservations.view']);

        CrmReservation::query()->create([
            'site_id' => $site->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $crmUser->id,
            'user_name' => $crmUser->name,
            'title' => 'Tournee test',
            'contact_phone' => '',
            'start_at' => '2026-08-03 08:00:00',
            'end_at' => '2026-08-03 09:00:00',
            'notes' => '',
        ]);

        $this->actingAs($account)
            ->getJson('/api/reservations?action=bootstrap')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('vehicles.0.name', 'Sprinter Test')
            ->assertJsonPath('reservations.0.title', 'Tournee test')
            ->assertJsonPath('user.siteIds.0', $site->id);
    }

    public function test_vehicle_day_hours_are_exposed_and_enforced(): void
    {
        [$account, , , $vehicle] = $this->createCrmUser(['reservations.view', 'reservations.create']);

        $vehicle->forceFill([
            'day_start_time' => '08:00',
            'day_end_time' => '16:30',
        ])->save();

        $this->actingAs($account)
            ->getJson('/api/reservations?action=bootstrap')
            ->assertOk()
            ->assertJsonPath('vehicles.0.dayStartTime', '08:00')
            ->assertJsonPath('vehicles.0.dayEndTime', '16:30');

        $this->actingAs($account)
            ->postJson('/api/reservations?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-04T07:30',
                'endAt' => '2026-08-04T08:30',
                'title' => 'Trop tot',
            ])
            ->assertStatus(400)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Creneau hors horaires du vehicule');

        $this->actingAs($account)
            ->postJson('/api/reservations?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-04T08:00',
                'endAt' => '2026-08-04T09:00',
                'title' => 'Dans les horaires',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reservation.title', 'Dans les horaires');
    }

    public function test_vehicle_default_day_hours_allow_extended_day_view_slots(): void
    {
        [$account, , , $vehicle] = $this->createCrmUser(['reservations.create']);

        $this->actingAs($account)
            ->postJson('/api/reservations?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-07T06:00',
                'endAt' => '2026-08-07T12:30',
                'title' => 'Matin par defaut',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reservation.startAt', '2026-08-07T06:00')
            ->assertJsonPath('reservation.endAt', '2026-08-07T12:30');

        $this->actingAs($account)
            ->postJson('/api/reservations?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-08T13:00',
                'endAt' => '2026-08-08T19:30',
                'title' => 'Apres-midi par defaut',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reservation.startAt', '2026-08-08T13:00')
            ->assertJsonPath('reservation.endAt', '2026-08-08T19:30');

        $this->actingAs($account)
            ->postJson('/api/reservations?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-09T05:30',
                'endAt' => '2026-08-09T06:30',
                'title' => 'Trop tot',
            ])
            ->assertStatus(400)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Creneau hors horaires du vehicule');
    }

    public function test_create_permission_is_required_to_create_reservation(): void
    {
        [$account, , , $vehicle] = $this->createCrmUser(['reservations.view']);

        $this->actingAs($account)
            ->postJson('/api/reservations?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-04T08:00',
                'endAt' => '2026-08-04T09:00',
                'title' => 'Sans droit',
            ])
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Droit insuffisant : reservations.create');
    }

    public function test_overlapping_reservation_is_rejected(): void
    {
        [$account, , , $vehicle] = $this->createCrmUser(['reservations.create']);

        $payload = [
            'vehicleId' => $vehicle->id,
            'startAt' => '2026-08-05T08:00',
            'endAt' => '2026-08-05T10:00',
            'title' => 'Premiere reservation',
        ];

        $this->actingAs($account)
            ->postJson('/api/reservations?action=create_reservation', $payload)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reservation.title', 'Premiere reservation');

        $this->actingAs($account)
            ->postJson('/api/reservations?action=create_reservation', [
                ...$payload,
                'startAt' => '2026-08-05T09:30',
                'endAt' => '2026-08-05T11:00',
            ])
            ->assertStatus(409)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Le vehicule est deja reserve sur ce creneau');
    }

    public function test_past_reservation_is_rejected(): void
    {
        [$account, , , $vehicle] = $this->createCrmUser(['reservations.create']);
        $yesterday = now()->subDay()->format('Y-m-d');

        $this->actingAs($account)
            ->postJson('/api/reservations?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => $yesterday.'T08:00',
                'endAt' => $yesterday.'T09:00',
                'title' => 'Reservation passee',
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Impossible de reserver dans le passe');
    }

    public function test_user_can_update_own_reservation(): void
    {
        [$account, , , $vehicle] = $this->createCrmUser(['reservations.create', 'reservations.update_own']);

        $reservationId = $this->actingAs($account)
            ->postJson('/api/reservations.php?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-06T08:00',
                'endAt' => '2026-08-06T09:00',
                'title' => 'A modifier',
            ])
            ->assertOk()
            ->json('reservation.id');

        $this->actingAs($account)
            ->postJson('/api/reservations?action=update_reservation', [
                'id' => $reservationId,
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-06T09:00',
                'endAt' => '2026-08-06T10:00',
                'title' => 'Modifie',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reservation.title', 'Modifie')
            ->assertJsonPath('reservation.startAt', '2026-08-06T09:00');
    }

    public function test_creator_can_delete_own_reservation_without_delete_permission(): void
    {
        [$account, , , $vehicle] = $this->createCrmUser(['reservations.create']);

        $reservationId = $this->actingAs($account)
            ->postJson('/api/reservations.php?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-07T08:00',
                'endAt' => '2026-08-07T09:00',
                'title' => 'A supprimer',
            ])
            ->assertOk()
            ->json('reservation.id');

        $this->actingAs($account)
            ->postJson('/api/reservations?action=delete_reservation', [
                'id' => $reservationId,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('crm_reservations', [
            'id' => $reservationId,
        ]);
    }

    public function test_updating_unknown_reservation_returns_not_found(): void
    {
        [$account, , , $vehicle] = $this->createCrmUser(['reservations.update']);

        $this->actingAs($account)
            ->postJson('/api/reservations?action=update_reservation', [
                'id' => 999999,
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-06T09:00',
                'endAt' => '2026-08-06T10:00',
                'title' => 'Introuvable',
            ])
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Reservation introuvable');
    }

    public function test_manager_can_save_and_hide_vehicle(): void
    {
        [$account, , $site] = $this->createCrmUser(['reservations.manage_vehicles']);

        $vehicleId = $this->actingAs($account)
            ->postJson('/api/reservations?action=save_vehicle', [
                'siteId' => $site->id,
                'name' => 'Camion test',
                'description' => 'Vehicule atelier',
                'color' => '#123abc',
                'dayStartTime' => '08:15',
                'dayEndTime' => '16:45',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('vehicle.name', 'Camion test')
            ->assertJsonPath('vehicle.dayStartTime', '08:15')
            ->assertJsonPath('vehicle.dayEndTime', '16:45')
            ->json('vehicle.id');

        $this->actingAs($account)
            ->postJson('/api/reservations?action=delete_vehicle', ['id' => $vehicleId])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('id', $vehicleId);

        $this->assertDatabaseHas('crm_vehicles', [
            'id' => $vehicleId,
            'active' => false,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array{0: User, 1: CrmUser, 2: CrmSite, 3: CrmVehicle}
     */
    private function createCrmUser(array $permissions): array
    {
        $account = User::factory()->create();
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'CRM Reservation User '.$account->id,
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

        $vehicle = CrmVehicle::query()->create([
            'site_id' => $site->id,
            'name' => 'Sprinter Test',
            'description' => 'Vehicule test',
            'color' => '#95002e',
            'active' => true,
        ]);

        $module = CrmModule::query()->updateOrCreate(
            ['slug' => 'reservations'],
            [
                'name' => 'Reservations',
                'description' => 'Reservations vehicules',
                'route_path' => '/reservations',
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
                    'group_label' => 'Reservations',
                    'sort_order' => 100 + $index,
                ],
            )->id;
        }

        $crmUser->sites()->syncWithoutDetaching([$site->id => ['is_default' => true]]);
        $crmUser->modules()->syncWithoutDetaching([$module->id]);
        $crmUser->permissions()->syncWithoutDetaching($permissionIds);

        return [$account, $crmUser, $site, $vehicle];
    }
}
