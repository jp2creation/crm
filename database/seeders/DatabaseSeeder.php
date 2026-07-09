<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminPassword = env('CRM_ADMIN_PASSWORD');

        if (! is_string($adminPassword) || $adminPassword === '') {
            throw new RuntimeException('CRM_ADMIN_PASSWORD must be set before seeding the default admin account.');
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $admin = User::updateOrCreate([
            'email' => env('CRM_ADMIN_EMAIL', 'admin@crm.jp2.fr'),
        ], [
            'name' => env('CRM_ADMIN_NAME', 'Administrateur'),
            'password' => Hash::make($adminPassword),
        ]);

        $admin->assignRole($adminRole);
    }
}
