<?php

namespace Tests\Feature;

use App\Models\CrmCashReceipt;
use App\Models\CrmCashRegisterDay;
use App\Models\CrmCheckRemittance;
use App\Models\CrmEquipmentItem;
use App\Models\CrmEquipmentRental;
use App\Models\CrmLeaveEmployee;
use App\Models\CrmLeaveEntry;
use App\Models\CrmModule;
use App\Models\CrmNotificationLog;
use App\Models\CrmReservation;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\CrmVehicle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CrmDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_cannot_read_dashboard(): void
    {
        $this->getJson('/api/dashboard')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Utilisateur CRM requis');
    }

    public function test_dashboard_returns_operational_widgets_for_authorized_user(): void
    {
        Carbon::setTestNow('2026-08-05 10:00:00');
        Cache::flush();

        $site = CrmSite::query()->create([
            'name' => 'Palissy',
            'slug' => 'palissy',
            'active' => true,
        ]);

        foreach ([
            ['Réservations véhicules', 'reservations', '/reservations'],
            ['Location matériel', 'locations-materiel', '/locations-materiel'],
            ['Congés', 'conges', '/conges'],
            ['Contrôle caisse', 'controle-caisse', '/controle-caisse'],
            ['Remise de chèques', 'remise-cheques', '/remise-cheques'],
        ] as [$name, $slug, $routePath]) {
            CrmModule::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $name,
                    'route_path' => $routePath,
                    'active' => true,
                    'sort_order' => 10,
                ],
            );
        }

        $account = User::factory()->create();
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'Jean-Philippe',
            'email' => $account->email,
            'role' => 'admin',
            'active' => true,
        ]);
        $crmUser->sites()->syncWithoutDetaching([$site->id => ['is_default' => true]]);

        $vehicle = CrmVehicle::query()->create([
            'site_id' => $site->id,
            'name' => 'Sprinter Test',
            'description' => '',
            'active' => true,
        ]);

        CrmReservation::query()->create([
            'site_id' => $site->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $crmUser->id,
            'user_name' => $crmUser->name,
            'title' => 'Chantier du jour',
            'start_at' => '2026-08-05 09:00:00',
            'end_at' => '2026-08-05 11:00:00',
        ]);

        $busyItem = CrmEquipmentItem::query()->create([
            'site_id' => $site->id,
            'name' => 'Ponceuse occupée',
            'active' => true,
        ]);
        CrmEquipmentItem::query()->create([
            'site_id' => $site->id,
            'name' => 'Monobrosse libre',
            'active' => true,
        ]);
        CrmEquipmentRental::query()->create([
            'site_id' => $site->id,
            'equipment_item_id' => $busyItem->id,
            'user_id' => $crmUser->id,
            'user_name' => $crmUser->name,
            'period_type' => 'day',
            'slot' => 'full_day',
            'status' => CrmEquipmentRental::STATUS_RESERVED,
            'title' => 'Location active',
            'start_at' => '2026-08-05 08:00:00',
            'end_at' => '2026-08-05 17:00:00',
        ]);

        $cashDay = CrmCashRegisterDay::query()->create([
            'site_id' => $site->id,
            'cash_date' => '2026-08-05',
            'status' => CrmCashRegisterDay::STATUS_OK,
        ]);
        CrmCashReceipt::query()->create([
            'cash_register_day_id' => $cashDay->id,
            'invoice_number' => 'F-001',
            'customer_name' => 'Client Test',
            'occurred_on' => '2026-08-05',
            'invoice_total' => 250.50,
            'cash_amount' => 250.50,
        ]);

        $employee = CrmLeaveEmployee::query()->firstOrCreate(
            ['crm_user_id' => $crmUser->id],
            [
                'name' => $crmUser->name,
                'slug' => 'jean-philippe-dashboard',
                'active' => true,
            ],
        );
        CrmLeaveEntry::query()->create([
            'employee_id' => $employee->id,
            'start_date' => '2026-08-06',
            'end_date' => '2026-08-06',
            'status' => 'pending',
            'type' => 'conge',
            'period' => 'full',
        ]);

        CrmCheckRemittance::query()->create([
            'site_id' => $site->id,
            'remittance_date' => '2026-08-05',
            'reference' => 'RC-TEST',
            'status' => CrmCheckRemittance::STATUS_DRAFT,
        ]);
        CrmNotificationLog::query()->create([
            'channel' => 'mail',
            'recipient' => 'test@example.com',
            'subject' => 'Test',
            'template_key' => 'test',
            'locale' => 'fr',
            'status' => CrmNotificationLog::STATUS_FAILED,
            'error_message' => 'Erreur test',
        ]);

        $this->actingAs($account)
            ->getJson('/api/dashboard?siteId='.$site->id)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('selectedSiteId', $site->id)
            ->assertJsonPath('stats.reservationsToday', 1)
            ->assertJsonPath('stats.monthlyRevenue', 250.5)
            ->assertJsonPath('stats.pendingLeaves', 1)
            ->assertJsonPath('stats.equipmentAvailable', 1)
            ->assertJsonPath('stats.equipmentTotal', 2)
            ->assertJsonPath('latestReservations.0.title', 'Chantier du jour')
            ->assertJsonPath('currentLeaves.0.name', 'Jean-Philippe')
            ->assertJsonPath('notifications.draftCheckRemittances', 1)
            ->assertJsonPath('notifications.failedNotifications', 1);
    }
}
