<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrmInfrastructureConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_horizon_gate_uses_spatie_platform_access(): void
    {
        $admin = User::factory()->create();
        $platformManager = User::factory()->create();
        $regular = User::factory()->create();

        Role::query()->create(['name' => 'admin', 'guard_name' => 'web']);
        Permission::query()->create(['name' => 'filament.access', 'guard_name' => 'web']);
        $admin->assignRole('admin');
        $platformManager->givePermissionTo('filament.access');

        $this->assertTrue($admin->canUsePlatformAdministration());
        $this->assertTrue($platformManager->canUsePlatformAdministration());
        $this->assertFalse($regular->canUsePlatformAdministration());
        $this->assertTrue(Gate::forUser($admin)->allows('viewHorizon'));
        $this->assertTrue(Gate::forUser($platformManager)->allows('viewHorizon'));
        $this->assertFalse(Gate::forUser($regular)->allows('viewHorizon'));
    }

    public function test_authenticated_non_database_session_is_mirrored_for_profile_devices(): void
    {
        config(['session.driver' => 'array']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->followingRedirects()
            ->withHeader('User-Agent', 'Chrome Test Agent')
            ->get('/dashboard/crm')
            ->assertSuccessful();

        $this->assertDatabaseHas('sessions', [
            'user_id' => $user->id,
            'user_agent' => 'Chrome Test Agent',
        ]);
    }
}
