<?php

namespace Tests\Feature;

use App\Models\CrmLeaveEmployee;
use App\Models\CrmModule;
use App\Models\CrmPermission;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmLeaveApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_read_leave_bootstrap(): void
    {
        $this->getJson('/api/conges?action=bootstrap')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Utilisateur CRM requis');
    }

    public function test_authorized_user_can_read_leave_bootstrap(): void
    {
        [$account, $crmUser, $site] = $this->createCrmUser(canManage: true);

        $this->actingAs($account)
            ->getJson('/api/conges?action=bootstrap&siteId='.$site->id)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('user.canManage', true)
            ->assertJsonPath('user.siteIds.0', $site->id)
            ->assertJsonPath('selectedSiteId', $site->id)
            ->assertJsonPath('employees.0.crmUserId', $crmUser->id)
            ->assertJsonPath('employees.0.name', $crmUser->name);
    }

    public function test_leave_bootstrap_uses_users_linked_to_selected_site(): void
    {
        [$account, $crmUser, $site] = $this->createCrmUser(canManage: true);
        $otherSite = $this->createSite('Autre Site');
        $otherUser = CrmUser::query()->create([
            'name' => 'Autre utilisateur',
            'role' => 'user',
            'active' => true,
        ]);
        $blockedUser = CrmUser::query()->create([
            'name' => 'Utilisateur sans acces',
            'role' => 'blocked',
            'active' => true,
        ]);
        $otherUser->sites()->syncWithoutDetaching([$otherSite->id => ['is_default' => true]]);
        $blockedUser->sites()->syncWithoutDetaching([$site->id => ['is_default' => false]]);

        $this->actingAs($account)
            ->getJson('/api/conges?action=bootstrap&siteId='.$site->id)
            ->assertOk()
            ->assertJsonFragment(['crmUserId' => $crmUser->id])
            ->assertJsonFragment(['crmUserId' => $blockedUser->id])
            ->assertJsonMissing(['crmUserId' => $otherUser->id]);
    }

    public function test_leave_employee_actions_are_not_available_from_module(): void
    {
        [$account] = $this->createCrmUser(canManage: true);

        $this->actingAs($account)
            ->postJson('/api/conges?action=save_employee', [
                'name' => 'Employe interdit',
            ])
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Action inconnue');
    }

    public function test_manage_permission_is_required_to_create_leave(): void
    {
        [$account, , , $employee] = $this->createCrmUser(canManage: false);

        $this->actingAs($account)
            ->postJson('/api/conges?action=save_leave', [
                'employeeId' => $employee->id,
                'startDate' => '2026-08-01',
                'endDate' => '2026-08-02',
            ])
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Droit insuffisant : conges.manage');
    }

    public function test_overlapping_leave_is_rejected(): void
    {
        [$account, , , $employee] = $this->createCrmUser(canManage: true);

        $payload = [
            'employeeId' => $employee->id,
            'startDate' => '2026-08-10',
            'endDate' => '2026-08-12',
            'type' => 'conge',
            'period' => 'full',
            'status' => 'approved',
        ];

        $this->actingAs($account)
            ->postJson('/api/conges?action=save_leave', $payload)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($account)
            ->postJson('/api/conges?action=save_leave', [
                ...$payload,
                'startDate' => '2026-08-11',
                'endDate' => '2026-08-13',
            ])
            ->assertStatus(409)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Un conge existe deja sur cette periode');
    }

    public function test_opposite_half_day_leaves_can_share_same_day(): void
    {
        [$account, , , $employee] = $this->createCrmUser(canManage: true);

        $this->actingAs($account)
            ->postJson('/api/conges?action=save_leave', [
                'employeeId' => $employee->id,
                'startDate' => '2026-08-14',
                'endDate' => '2026-08-14',
                'type' => 'conge',
                'period' => 'morning',
                'status' => 'approved',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($account)
            ->postJson('/api/conges?action=save_leave', [
                'employeeId' => $employee->id,
                'startDate' => '2026-08-14',
                'endDate' => '2026-08-14',
                'type' => 'conge',
                'period' => 'afternoon',
                'status' => 'approved',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_same_half_day_leave_is_rejected(): void
    {
        [$account, , , $employee] = $this->createCrmUser(canManage: true);

        $payload = [
            'employeeId' => $employee->id,
            'startDate' => '2026-08-15',
            'endDate' => '2026-08-15',
            'type' => 'conge',
            'period' => 'morning',
            'status' => 'approved',
        ];

        $this->actingAs($account)
            ->postJson('/api/conges?action=save_leave', $payload)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($account)
            ->postJson('/api/conges?action=save_leave', $payload)
            ->assertStatus(409)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Un conge existe deja sur cette periode');
    }

    /**
     * @return array{0: User, 1: CrmUser, 2: CrmSite, 3: CrmLeaveEmployee}
     */
    private function createCrmUser(bool $canManage): array
    {
        $account = User::factory()->create();
        $site = $this->createSite('Palissy Test');
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'CRM Test User '.$account->id,
            'role' => $canManage ? 'admin' : 'user',
            'active' => true,
        ]);
        $employee = CrmLeaveEmployee::query()->create([
            'crm_user_id' => $crmUser->id,
            'name' => $crmUser->name,
            'slug' => 'crm-test-user-'.$account->id,
            'color' => '#2563eb',
            'active' => true,
            'sort_order' => 10,
        ]);

        $module = CrmModule::query()->updateOrCreate(
            ['slug' => 'conges'],
            [
                'name' => 'Conges',
                'description' => 'Planning conges',
                'route_path' => '/conges',
                'active' => true,
                'sort_order' => 24,
            ],
        );

        $view = CrmPermission::query()->updateOrCreate(
            ['name' => 'conges.view'],
            ['label' => 'Voir les conges', 'group_label' => 'Conges', 'sort_order' => 185],
        );

        $manage = CrmPermission::query()->updateOrCreate(
            ['name' => 'conges.manage'],
            ['label' => 'Gerer les conges', 'group_label' => 'Conges', 'sort_order' => 186],
        );

        $crmUser->sites()->syncWithoutDetaching([$site->id => ['is_default' => true]]);
        $crmUser->modules()->syncWithoutDetaching([$module->id]);
        $crmUser->permissions()->syncWithoutDetaching(
            $canManage ? [$view->id, $manage->id] : [$view->id],
        );

        return [$account, $crmUser, $site, $employee];
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
