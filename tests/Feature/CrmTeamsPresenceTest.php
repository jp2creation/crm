<?php

namespace Tests\Feature;

use App\Models\CrmModule;
use App\Models\CrmPermission;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrmTeamsPresenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_team_member_presence(): void
    {
        [$account, , $palissy] = $this->createCrmContext(role: 'admin');
        $onlineAccount = User::factory()->create(['email' => 'marie@example.test']);
        $offlineAccount = User::factory()->create(['email' => 'paul@example.test']);

        $this->createMember($palissy, [
            'user_id' => $onlineAccount->id,
            'name' => 'Marie Durand',
            'first_name' => 'Marie',
            'last_name' => 'Durand',
            'email' => 'marie@example.test',
        ]);
        $this->createMember($palissy, [
            'user_id' => $offlineAccount->id,
            'name' => 'Paul Martin',
            'first_name' => 'Paul',
            'last_name' => 'Martin',
            'email' => 'paul@example.test',
        ]);

        DB::table((string) config('session.table', 'sessions'))->insert([
            'id' => 'online-session',
            'user_id' => $onlineAccount->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($account)
            ->getJson('/api/equipes?action=bootstrap&siteId='.$palissy->id);

        $response
            ->assertOk()
            ->assertJsonPath('user.canViewPresence', true);

        $members = collect($response->json('members'));
        $onlineMember = $members->firstWhere('email', 'marie@example.test');
        $offlineMember = $members->firstWhere('email', 'paul@example.test');

        $this->assertIsArray($onlineMember);
        $this->assertIsArray($offlineMember);
        $this->assertSame(true, data_get($onlineMember, 'presence.isOnline'));
        $this->assertSame('En ligne', data_get($onlineMember, 'presence.label'));
        $this->assertSame(false, data_get($offlineMember, 'presence.isOnline'));
        $this->assertSame('Hors ligne', data_get($offlineMember, 'presence.label'));
    }

    public function test_non_admin_cannot_see_team_member_presence(): void
    {
        [$account, , $palissy] = $this->createCrmContext();
        $onlineAccount = User::factory()->create(['email' => 'marie@example.test']);

        $this->createMember($palissy, [
            'user_id' => $onlineAccount->id,
            'name' => 'Marie Durand',
            'first_name' => 'Marie',
            'last_name' => 'Durand',
            'email' => 'marie@example.test',
        ]);

        DB::table((string) config('session.table', 'sessions'))->insert([
            'id' => 'online-session',
            'user_id' => $onlineAccount->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($account)
            ->getJson('/api/equipes?action=bootstrap&siteId='.$palissy->id);

        $response
            ->assertOk()
            ->assertJsonPath('user.canViewPresence', false);

        foreach ($response->json('members') as $member) {
            $this->assertArrayNotHasKey('presence', $member);
        }
    }

    /**
     * @return array{0: User, 1: CrmUser, 2: CrmSite}
     */
    private function createCrmContext(string $role = 'user'): array
    {
        $palissy = CrmSite::query()->create([
            'name' => 'Palissy',
            'slug' => 'palissy',
            'active' => true,
        ]);

        $module = CrmModule::query()->updateOrCreate(
            ['slug' => 'equipes'],
            [
                'name' => 'Équipe',
                'description' => 'Annuaire des membres',
                'route_path' => '/equipes',
                'active' => true,
                'sort_order' => 16,
            ],
        );
        $permission = CrmPermission::query()->updateOrCreate(
            ['name' => 'teams.view'],
            [
                'label' => 'Voir les equipes',
                'group_label' => 'Equipe',
                'sort_order' => 155,
            ],
        );

        $account = User::factory()->create([
            'name' => 'Jean Martin',
            'email' => 'jean@example.test',
        ]);
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'Jean Martin',
            'first_name' => 'Jean',
            'last_name' => 'Martin',
            'email' => 'jean@example.test',
            'phone' => '06 00 00 00 00',
            'role' => $role,
            'active' => true,
        ]);
        $crmUser->sites()->sync([$palissy->id => ['is_default' => true]]);
        $crmUser->modules()->sync([$module->id]);
        $crmUser->permissions()->sync([$permission->id]);

        return [$account, $crmUser, $palissy];
    }

    private function createMember(CrmSite $site, array $attributes): CrmUser
    {
        $member = CrmUser::query()->create(array_merge([
            'role' => 'user',
            'active' => true,
        ], $attributes));
        $member->sites()->sync([$site->id => ['is_default' => true]]);

        return $member;
    }
}
