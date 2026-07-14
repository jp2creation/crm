<?php

namespace Modules\CrmAdministration\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AdminAccountService
{
    public function upsertDefaultAdmin(string $email, string $name, string $password): User
    {
        $this->assertValidIdentity($email, $name);
        $this->assertStrongPassword($password);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $admin = User::query()->updateOrCreate([
            'email' => $email,
        ], [
            'name' => $name,
            'password' => Hash::make($password, [
                'rounds' => max(12, (int) config('crm.admin_password.hash_rounds', 12)),
            ]),
        ]);

        $admin->assignRole($adminRole);

        return $admin;
    }

    public function assertStrongPassword(string $password): void
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
            throw ValidationException::withMessages([
                'password' => $validator->errors()->all(),
            ]);
        }
    }

    private function assertValidIdentity(string $email, string $name): void
    {
        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
        ], [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->messages());
        }
    }
}
