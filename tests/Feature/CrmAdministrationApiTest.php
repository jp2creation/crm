<?php

namespace Tests\Feature;

use App\Models\CrmMenuGroup;
use App\Models\CrmMenuItem;
use App\Models\CrmModule;
use App\Models\CrmPermission;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmAdministrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_does_not_require_authentication(): void
    {
        $this->getJson('/api/administration?action=health')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('mode', 'laravel');
    }

    public function test_guest_cannot_read_administration_bootstrap(): void
    {
        $this->getJson('/api/administration?action=bootstrap')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Utilisateur CRM requis');
    }

    public function test_admin_can_read_bootstrap_from_legacy_endpoint(): void
    {
        [$account] = $this->createAdminUser();

        $this->actingAs($account)
            ->getJson('/api/administration.php?action=bootstrap')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('roles.2.key', 'admin')
            ->assertJsonPath('modules.0.slug', 'reservations')
            ->assertJsonPath('menuGroups.0.menuKey', 'apps')
            ->assertJsonPath('actor.name', 'J-Philippe');
    }

    public function test_administration_bootstrap_cleans_template_menu_entries(): void
    {
        [$account] = $this->createAdminUser();

        CrmMenuGroup::query()->create([
            'menu_key' => 'dashboards',
            'title' => 'Dashboards',
            'active' => true,
            'sort_order' => 10,
        ]);
        CrmMenuGroup::query()->create([
            'menu_key' => 'charts',
            'title' => 'Charts',
            'active' => true,
            'sort_order' => 80,
        ]);
        CrmMenuItem::query()->create([
            'item_key' => 'dashboard:analytics',
            'group_key' => 'dashboards',
            'icon_key' => 'chartLine',
            'label' => 'Analytics',
            'active' => true,
            'sort_order' => 20,
        ]);
        CrmMenuItem::query()->create([
            'item_key' => 'chart:line',
            'group_key' => 'charts',
            'icon_key' => 'chartLine',
            'label' => 'Line',
            'active' => true,
            'sort_order' => 10,
        ]);

        $this->actingAs($account)
            ->getJson('/api/administration?action=bootstrap')
            ->assertOk()
            ->assertJsonMissing(['itemKey' => 'dashboard:analytics'])
            ->assertJsonMissing(['itemKey' => 'chart:line']);

        $this->assertDatabaseMissing('crm_menu_items', ['item_key' => 'dashboard:analytics']);
        $this->assertDatabaseMissing('crm_menu_items', ['item_key' => 'chart:line']);
        $this->assertDatabaseMissing('crm_menu_groups', ['menu_key' => 'dashboards']);
        $this->assertDatabaseHas('crm_menu_groups', [
            'menu_key' => 'apps',
            'title' => 'Applications CRM',
            'active' => true,
        ]);
        $this->assertDatabaseHas('crm_menu_items', [
            'item_key' => 'module:reservations',
            'group_key' => 'apps',
            'active' => true,
        ]);
        $this->assertDatabaseHas('crm_menu_items', [
            'item_key' => 'module:planning',
            'active' => false,
        ]);
    }

    public function test_user_without_platform_permission_cannot_read_bootstrap(): void
    {
        $account = User::factory()->create();
        CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'Simple User',
            'role' => 'user',
            'active' => true,
        ]);

        $this->actingAs($account)
            ->getJson('/api/administration?action=bootstrap')
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Droit administration insuffisant');
    }

    public function test_admin_can_save_site_with_hours(): void
    {
        [$account] = $this->createAdminUser();

        $siteId = $this->actingAs($account)
            ->postJson('/api/administration?action=save_site', [
                'name' => 'Atelier Nord',
                'active' => true,
                'hours' => [
                    'morningStart' => '08:00',
                    'morningEnd' => '12:15',
                    'afternoonStart' => '13:45',
                    'afternoonEnd' => '18:00',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->json('id');

        $this->assertDatabaseHas('crm_sites', [
            'id' => $siteId,
            'name' => 'Atelier Nord',
            'slug' => 'atelier-nord',
            'morning_start' => '08:00:00',
            'afternoon_end' => '18:00:00',
        ]);
    }

    public function test_admin_can_create_blocked_user_without_rights(): void
    {
        [$account] = $this->createAdminUser();

        $this->actingAs($account)
            ->getJson('/api/administration?action=bootstrap')
            ->assertOk();

        $siteId = CrmSite::query()->value('id');
        $moduleId = CrmModule::query()->value('id');
        $permissionId = CrmPermission::query()->value('id');

        $userId = $this->actingAs($account)
            ->postJson('/api/administration?action=save_user', [
                'name' => 'Compte bloque',
                'role' => 'blocked',
                'active' => true,
                'siteIds' => [$siteId],
                'moduleIds' => [$moduleId],
                'permissionIds' => [$permissionId],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->json('id');

        $this->assertDatabaseHas('crm_users', [
            'id' => $userId,
            'name' => 'Compte bloque',
            'role' => 'blocked',
        ]);
        $this->assertDatabaseMissing('crm_user_modules', ['user_id' => $userId]);
        $this->assertDatabaseMissing('crm_user_permissions', ['user_id' => $userId]);
        $this->assertDatabaseHas('crm_user_sites', ['user_id' => $userId, 'site_id' => $siteId]);
    }

    /**
     * @return array{0: User, 1: CrmUser}
     */
    private function createAdminUser(): array
    {
        $account = User::factory()->create();
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'J-Philippe',
            'role' => 'admin',
            'active' => true,
        ]);

        return [$account, $crmUser];
    }
}
