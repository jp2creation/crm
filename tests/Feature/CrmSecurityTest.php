<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CrmSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_throttles_repeated_failed_attempts(): void
    {
        RateLimiter::clear('bruteforce@example.test|127.0.0.1');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/login')
                ->post('/login', [
                    'email' => 'bruteforce@example.test',
                    'password' => 'mauvais-mot-de-passe',
                ])
                ->assertRedirect('/login')
                ->assertSessionHasErrors('email');
        }

        $response = $this->from('/login')
            ->post('/login', [
                'email' => 'bruteforce@example.test',
                'password' => 'mauvais-mot-de-passe',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Trop de tentatives.',
            $response->getSession()->get('errors')->first('email'),
        );
    }

    public function test_login_remember_me_sets_the_laravel_recaller_cookie(): void
    {
        RateLimiter::clear('remember@example.test|127.0.0.1');

        $user = User::factory()->create([
            'email' => 'remember@example.test',
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'remember' => '1',
            ])
            ->assertRedirect('/')
            ->assertCookie(Auth::guard('web')->getRecallerName());
    }

    public function test_login_does_not_keep_mobile_embed_for_the_regular_pwa(): void
    {
        RateLimiter::clear('pwa@example.test|127.0.0.1');

        $user = User::factory()->create([
            'email' => 'pwa@example.test',
        ]);

        $this->withSession(['url.intended' => '/?mobile_embed=1&mobile_site_id=3&source=pwa'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'remember' => '1',
            ])
            ->assertRedirect('/?source=pwa');
    }

    public function test_crm_shell_intercepts_the_legacy_template_logout_link(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-crm-logout-bridge', $html);
        $this->assertStringContainsString('/auth/login', $html);
        $this->assertStringContainsString('logoutToCrmLogin', $html);
    }

    public function test_csrf_middleware_rejects_missing_token_when_enabled(): void
    {
        $middleware = new class($this->app, $this->app['encrypter']) extends VerifyCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        };

        $request = Request::create('/logout', 'POST');
        $request->setLaravelSession($this->app['session']->driver());

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn (): Response => new Response('ok'));
    }
}
