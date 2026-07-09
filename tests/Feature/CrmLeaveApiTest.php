<?php

namespace Tests\Feature;

use App\Models\CrmLeaveEmployee;
use App\Models\CrmModule;
use App\Models\CrmPermission;
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
        [$account] = $this->createCrmUser(canManage: true);

        CrmLeaveEmployee::query()->create([
            'name' => 'TEST CONGES',
            'slug' => 'test-conges',
            'color' => '#2563eb',
            'active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($account)
            ->getJson('/api/conges?action=bootstrap')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('user.canManage', true)
            ->assertJsonFragment(['slug' => 'test-conges']);
    }

    public function test_manage_permission_is_required_to_create_leave(): void
    {
        [$account] = $this->createCrmUser(canManage: false);
        $employee = CrmLeaveEmployee::query()->firstOrFail();

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
        [$account] = $this->createCrmUser(canManage: true);
        $employee = CrmLeaveEmployee::query()->create([
            'name' => 'TEST OVERLAP',
            'slug' => 'test-overlap',
            'color' => '#16a34a',
            'active' => true,
            'sort_order' => 1,
        ]);

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

    /**
     * @return array{0: User, 1: CrmUser}
     */
    private function createCrmUser(bool $canManage): array
    {
        $account = User::factory()->create();
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'CRM Test User '.$account->id,
            'role' => $canManage ? 'admin' : 'user',
            'active' => true,
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

        $crmUser->modules()->syncWithoutDetaching([$module->id]);
        $crmUser->permissions()->syncWithoutDetaching(
            $canManage ? [$view->id, $manage->id] : [$view->id],
        );

        return [$account, $crmUser];
    }
}
