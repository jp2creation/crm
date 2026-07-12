<?php

namespace Tests\Unit;

use App\Services\Crm\AdminAccountService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminAccountServiceTest extends TestCase
{
    public function test_it_accepts_a_strong_admin_password(): void
    {
        app(AdminAccountService::class)->assertStrongPassword('Solide-Admin-2026!');

        $this->assertTrue(true);
    }

    public function test_it_rejects_a_weak_admin_password(): void
    {
        $this->expectException(ValidationException::class);

        app(AdminAccountService::class)->assertStrongPassword('faible');
    }
}
