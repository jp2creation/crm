<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $admin = User::updateOrCreate([
            'email' => env('CRM_ADMIN_EMAIL', 'admin@crm.jp2.fr'),
        ], [
            'name' => env('CRM_ADMIN_NAME', 'Administrateur'),
            'password' => Hash::make(env('CRM_ADMIN_PASSWORD', 'ChangeMe-CRM-2026!')),
        ]);

        $admin->assignRole($adminRole);
    }
}
