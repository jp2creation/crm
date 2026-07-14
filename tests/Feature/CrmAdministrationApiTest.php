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

    public function test_admin_can_read_and_save_profile(): void
    {
        [$account, $crmUser] = $this->createAdminUser();

        $this->actingAs($account)
            ->getJson('/api/administration?action=profile')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('profile.displayName', 'Jean-Philippe')
            ->assertJsonPath('profile.firstName', 'Jean-Philippe')
            ->assertJsonPath('profile.email', $account->email)
            ->assertJsonPath('profile.photoUrl', '/assets/logo/logomark.png')
            ->assertJsonPath('profile.canEditIdentity', true);

        $profile = $this->actingAs($account)
            ->postJson('/api/administration?action=save_profile', [
                'firstName' => 'Jean-Philippe',
                'lastName' => 'Martin',
                'email' => 'jp.martin@example.test',
                'bio' => 'Direction CRM',
                'photoDataUrl' => 'data:image/png;base64,'.base64_encode('fake png content'),
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('profile.displayName', 'Jean-Philippe Martin')
            ->assertJsonPath('profile.email', 'jp.martin@example.test')
            ->assertJsonPath('profile.bio', 'Direction CRM')
            ->json('profile');

        $this->assertStringStartsWith('/assets/uploads/profiles/photo_', $profile['photoUrl']);

        $this->assertDatabaseHas('crm_users', [
            'id' => $crmUser->id,
            'name' => 'Jean-Philippe Martin',
            'first_name' => 'Jean-Philippe',
            'last_name' => 'Martin',
            'email' => 'jp.martin@example.test',
            'bio' => 'Direction CRM',
            'photo_url' => $profile['photoUrl'],
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $account->id,
            'name' => 'Jean-Philippe Martin',
            'email' => 'jp.martin@example.test',
        ]);

        @unlink(public_path(ltrim($profile['photoUrl'], '/')));
    }

    public function test_non_admin_profile_cannot_change_identity(): void
    {
        $account = User::factory()->create([
            'name' => 'Marie Durand',
            'email' => 'marie.old@example.test',
        ]);
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'Marie Durand',
            'first_name' => 'Marie',
            'last_name' => 'Durand',
            'email' => 'marie.old@example.test',
            'role' => 'user',
            'active' => true,
        ]);

        $this->actingAs($account)
            ->postJson('/api/administration?action=save_profile', [
                'firstName' => 'Pirate',
                'lastName' => 'Invisible',
                'email' => 'marie.new@example.test',
                'bio' => 'Equipe Palissy',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('profile.displayName', 'Marie Durand')
            ->assertJsonPath('profile.firstName', 'Marie')
            ->assertJsonPath('profile.lastName', 'Durand')
            ->assertJsonPath('profile.email', 'marie.new@example.test')
            ->assertJsonPath('profile.bio', 'Equipe Palissy')
            ->assertJsonPath('profile.canEditIdentity', false);

        $this->assertDatabaseHas('crm_users', [
            'id' => $crmUser->id,
            'name' => 'Marie Durand',
            'first_name' => 'Marie',
            'last_name' => 'Durand',
            'email' => 'marie.new@example.test',
            'bio' => 'Equipe Palissy',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $account->id,
            'name' => 'Marie Durand',
            'email' => 'marie.new@example.test',
        ]);
    }

    public function test_profile_rejects_duplicate_account_email(): void
    {
        [$account] = $this->createAdminUser();
        User::factory()->create(['email' => 'already-used@example.test']);

        $this->actingAs($account)
            ->postJson('/api/administration?action=save_profile', [
                'firstName' => 'Jean-Philippe',
                'lastName' => 'Martin',
                'email' => 'already-used@example.test',
            ])
            ->assertStatus(400)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Adresse e-mail deja utilisee');
    }

    public function test_admin_can_read_bootstrap_from_legacy_endpoint(): void
    {
        [$account] = $this->createAdminUser();

        $this->actingAs($account)
            ->getJson('/api/administration.php?action=bootstrap')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('roles.2.key', 'admin')
            ->assertJsonPath('modules.0.slug', 'dashboard')
            ->assertJsonPath('menuGroups.0.menuKey', 'home')
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
            'menu_key' => 'home',
            'title' => 'Accueil',
            'active' => true,
        ]);
        $this->assertDatabaseHas('crm_modules', [
            'slug' => 'dashboard',
            'route_path' => '/dashboard/crm',
            'active' => true,
        ]);
        $this->assertDatabaseHas('crm_menu_items', [
            'item_key' => 'module:dashboard',
            'group_key' => 'home',
            'label' => 'Tableau de bord',
            'active' => true,
        ]);
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
        $this->assertDatabaseMissing('crm_menu_groups', ['menu_key' => 'check_remittances']);
        $this->assertDatabaseHas('crm_menu_groups', [
            'menu_key' => 'accounting',
            'title' => 'Comptabilité',
            'active' => true,
        ]);
        $this->assertDatabaseHas('crm_menu_items', [
            'item_key' => 'module:remise-cheques',
            'group_key' => 'accounting',
            'label' => 'Remise de chèques',
            'active' => true,
        ]);
        $this->assertDatabaseHas('crm_modules', [
            'slug' => 'addvance',
            'route_path' => 'https://martinsols.addvancesolutions.fr',
            'active' => true,
        ]);
        $this->assertDatabaseHas('crm_menu_items', [
            'item_key' => 'module:addvance',
            'group_key' => 'accounting',
            'label' => 'Addvance',
            'active' => true,
        ]);
    }

    public function test_menu_settings_keep_custom_label_and_visibility_after_bootstrap(): void
    {
        [$account] = $this->createAdminUser();

        $bootstrap = $this->actingAs($account)
            ->getJson('/api/administration?action=bootstrap')
            ->assertOk()
            ->json();

        $congesItem = collect($bootstrap['menuItems'])
            ->firstWhere('itemKey', 'module:conges');

        $this->assertNotNull($congesItem);

        $this->actingAs($account)
            ->postJson('/api/administration?action=save_menu_settings', [
                'items' => [[
                    'itemKey' => 'module:conges',
                    'groupKey' => $congesItem['groupKey'],
                    'iconKey' => $congesItem['iconKey'],
                    'label' => 'Absences équipe',
                    'active' => false,
                    'sortOrder' => $congesItem['sortOrder'],
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('crm_menu_items', [
            'item_key' => 'module:conges',
            'label' => 'Absences équipe',
            'active' => false,
        ]);

        $this->actingAs($account)
            ->getJson('/api/administration?action=bootstrap')
            ->assertOk()
            ->assertJsonFragment([
                'itemKey' => 'module:conges',
                'label' => 'Absences équipe',
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
