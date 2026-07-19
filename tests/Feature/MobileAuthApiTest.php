<?php

namespace Tests\Feature;

use App\Models\CrmModule;
use App\Models\CrmPermission;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_token_can_authenticate_crm_api_requests(): void
    {
        [$account, $crmUser] = $this->createMobileCrmUser();

        $token = $this->postJson('/api/mobile/token', [
            'email' => $account->email,
            'password' => 'secret-mobile',
            'device_name' => 'Pixel Test',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('tokenType', 'Bearer')
            ->assertJsonPath('user.modules.0.slug', 'reservations')
            ->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reservations?action=bootstrap')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('user.name', $crmUser->name);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('user.name', $crmUser->name)
            ->assertJsonPath('user.sites.0.name', 'Palissy Mobile')
            ->assertJsonPath('user.menu.0.items.0.slug', 'reservations');

        $webSessionUrl = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/web-session', [
                'redirectPath' => '/reservations',
                'siteId' => $crmUser->sites()->first()->id,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->json('url');

        $webSessionPath = parse_url((string) $webSessionUrl, PHP_URL_PATH);

        $this->get($webSessionPath)
            ->assertRedirect('/reservations?mobile_embed=1&mobile_site_id='.$crmUser->sites()->first()->id);

        $this->assertAuthenticatedAs($account);

        $this->get($webSessionPath)
            ->assertNotFound();

        $this->flushSession();

        $fullWebSessionUrl = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/web-session', [
                'redirectPath' => '/',
                'siteId' => $crmUser->sites()->first()->id,
                'embed' => false,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->json('url');

        $fullWebSessionPath = parse_url((string) $fullWebSessionUrl, PHP_URL_PATH);

        $this->get($fullWebSessionPath)
            ->assertRedirect('/?mobile_app=1&mobile_site_id='.$crmUser->sites()->first()->id);

        $this->assertTrue(session()->has('crm_mobile_app'));

        $this->flushSession();

        $dashboardWebSessionUrl = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/web-session', [
                'redirectPath' => '/dashboard/crm?mobile_app=1',
                'siteId' => $crmUser->sites()->first()->id,
                'embed' => false,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->json('url');

        $dashboardWebSessionPath = parse_url((string) $dashboardWebSessionUrl, PHP_URL_PATH);

        $this->get($dashboardWebSessionPath)
            ->assertRedirect('/dashboard/crm?mobile_app=1&mobile_site_id='.$crmUser->sites()->first()->id);

        $this->flushSession();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/logout')
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/me')
            ->assertUnauthorized();
    }

    public function test_blocked_crm_user_cannot_receive_mobile_token(): void
    {
        [$account] = $this->createMobileCrmUser(role: 'blocked');

        $this->postJson('/api/mobile/token', [
            'email' => $account->email,
            'password' => 'secret-mobile',
            'device_name' => 'Pixel Test',
        ])
            ->assertForbidden()
            ->assertJsonPath('ok', false);
    }

    public function test_mobile_web_session_logout_revokes_the_launching_mobile_token(): void
    {
        [$account, $crmUser] = $this->createMobileCrmUser();

        $token = $this->postJson('/api/mobile/token', [
            'email' => $account->email,
            'password' => 'secret-mobile',
            'device_name' => 'Pixel Test',
        ])
            ->assertOk()
            ->json('token');

        $webSessionUrl = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/web-session', [
                'redirectPath' => '/',
                'siteId' => $crmUser->sites()->first()->id,
                'embed' => false,
            ])
            ->assertOk()
            ->json('url');

        $webSessionPath = parse_url((string) $webSessionUrl, PHP_URL_PATH);

        $this->get($webSessionPath)
            ->assertRedirect('/?mobile_app=1&mobile_site_id='.$crmUser->sites()->first()->id);

        $this->assertAuthenticatedAs($account);
        $this->assertTrue(session()->has('crm_mobile_token_id'));
        $this->assertTrue(session()->has('crm_mobile_app'));

        $this->post('/logout')
            ->assertRedirect('/login');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/me')
            ->assertUnauthorized();
    }

    public function test_mobile_app_session_shows_app_settings_entry_in_crm(): void
    {
        [$account] = $this->createMobileCrmUser();

        $this->actingAs($account)
            ->withSession(['crm_mobile_app' => true])
            ->get('/')
            ->assertOk()
            ->assertSee('data-crm-mobile-settings-toggle', false)
            ->assertSee('data-crm-mobile-fallback-nav', false);

        $this->flushSession();

        $this->actingAs($account)
            ->get('/?mobile_app=1')
            ->assertOk()
            ->assertSee('data-crm-mobile-settings-toggle', false)
            ->assertSee('data-crm-mobile-fallback-nav', false);

        $this->flushSession();

        $this->actingAs($account)
            ->get('/')
            ->assertOk()
            ->assertDontSee('data-crm-mobile-settings-toggle', false);
    }

    public function test_mobile_tokens_are_revoked_after_password_reset_event(): void
    {
        [$account] = $this->createMobileCrmUser();

        $account->createToken('Pixel Test', ['crm:mobile']);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        event(new PasswordReset($account));

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * @return array{0: User, 1: CrmUser}
     */
    private function createMobileCrmUser(string $role = 'user'): array
    {
        $account = User::factory()->create([
            'email' => 'mobile-'.$role.'@example.test',
            'password' => 'secret-mobile',
        ]);

        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'Mobile '.$role,
            'email' => $account->email,
            'role' => $role,
            'active' => true,
        ]);

        $site = CrmSite::query()->create([
            'name' => 'Palissy Mobile',
            'slug' => 'palissy-mobile',
            'active' => true,
            'morning_start' => '07:30:00',
            'morning_end' => '12:00:00',
            'afternoon_start' => '13:30:00',
            'afternoon_end' => '17:30:00',
        ]);

        $module = CrmModule::query()->create([
            'name' => 'Reservations',
            'slug' => 'reservations',
            'description' => 'Reservations vehicules',
            'route_path' => '/reservations',
            'active' => true,
            'sort_order' => 10,
        ]);

        $permission = CrmPermission::query()->create([
            'name' => 'reservations.view',
            'label' => 'Voir les reservations',
            'group_label' => 'Reservations',
            'sort_order' => 10,
        ]);

        $crmUser->sites()->sync([$site->id => ['is_default' => true]]);
        $crmUser->modules()->sync([$module->id]);
        $crmUser->permissions()->sync([$permission->id]);

        return [$account, $crmUser];
    }
}
