<?php

namespace Tests\Feature;

use App\Models\CrmUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmAccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_settings_is_a_protected_crm_page_not_a_legacy_404(): void
    {
        $this->get('/pages/account-settings')
            ->assertRedirect('/login');

        [$account] = $this->createCrmAccount();

        $this->actingAs($account)
            ->get('/pages/account-settings')
            ->assertOk()
            ->assertSee('crm-shell-config', false);

        $register = (string) file_get_contents(resource_path('frontend/crm/modules/register.ts'));

        $this->assertStringContainsString('crm-account-settings.js', $register);
    }

    public function test_profile_api_uses_the_same_identity_as_account_settings(): void
    {
        [$account] = $this->createCrmAccount();

        $this->actingAs($account)
            ->getJson('/api/administration?action=profile')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('profile.displayName', 'Jean-Philippe JP2 Creation')
            ->assertJsonPath('profile.email', 'peinture.pau@martinsols.com')
            ->assertJsonPath('profile.role', 'admin');
    }

    public function test_profile_can_be_saved_from_account_settings(): void
    {
        [$account] = $this->createCrmAccount();

        $this->actingAs($account)
            ->postJson('/api/administration?action=save_profile', [
                'firstName' => 'Jean-Philippe',
                'lastName' => 'Martin',
                'email' => 'peinture.pau@martinsols.com',
                'bio' => 'Administrateur CRM Martin Sols',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('profile.displayName', 'Jean-Philippe Martin')
            ->assertJsonPath('profile.bio', 'Administrateur CRM Martin Sols');

        $this->assertDatabaseHas('crm_users', [
            'first_name' => 'Jean-Philippe',
            'last_name' => 'Martin',
            'email' => 'peinture.pau@martinsols.com',
        ]);
    }

    /**
     * @return array{0: User, 1: CrmUser}
     */
    private function createCrmAccount(): array
    {
        $account = User::factory()->create([
            'name' => 'Jean-Philippe JP2 Creation',
            'email' => 'peinture.pau@martinsols.com',
        ]);

        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'Jean-Philippe JP2 Creation',
            'first_name' => 'Jean-Philippe',
            'last_name' => 'JP2 Creation',
            'email' => 'peinture.pau@martinsols.com',
            'bio' => 'Administrateur CRM Martin Sols',
            'photo_url' => '/assets/logo/logomark.png',
            'role' => 'admin',
            'active' => true,
        ]);

        return [$account, $crmUser];
    }
}
