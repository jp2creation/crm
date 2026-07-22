<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CrmStats\Filament\Pages\StatsDashboard;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CrmStatsFilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_pages_are_hidden_without_view_stats_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertFalse(StatsDashboard::canAccess());
        $this->assertFalse(StatsDashboard::shouldRegisterNavigation());
    }

    public function test_stats_pages_are_visible_with_view_stats_permission(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()->firstOrCreate(['name' => 'view_stats', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('view_stats');

        $this->actingAs($user);

        $this->assertTrue(StatsDashboard::canAccess());
        $this->assertTrue(StatsDashboard::shouldRegisterNavigation());
    }

    public function test_platform_admin_can_render_stats_dashboard(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin/stats')
            ->assertOk()
            ->assertSee('Stats commerciales');
    }
}
