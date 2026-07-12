<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
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

        $this->validateAdminPassword($adminPassword);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $admin = User::updateOrCreate([
            'email' => env('CRM_ADMIN_EMAIL', 'admin@crm.jp2.fr'),
        ], [
            'name' => env('CRM_ADMIN_NAME', 'Administrateur'),
            'password' => Hash::make($adminPassword, [
                'rounds' => max(12, (int) config('crm.admin_password.hash_rounds', 12)),
            ]),
        ]);

        $admin->assignRole($adminRole);
    }

    private function validateAdminPassword(string $password): void
    {
        $validator = Validator::make([
            'password' => $password,
        ], [
            'password' => [
                'required',
                'string',
                Password::min(max(12, (int) config('crm.admin_password.min_length', 12)))
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException(
                'CRM_ADMIN_PASSWORD does not satisfy the required password policy: '.
                implode(' ', $validator->errors()->all())
            );
        }
    }
}
