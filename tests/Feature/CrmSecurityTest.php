<?php

namespace Tests\Feature;

use App\Models\CrmUser;
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

    public function test_legacy_php_api_can_be_disabled_in_production(): void
    {
        config(['crm.legacy_php_api.enabled' => false]);

        $this->getJson('/api/administration.php?action=health')
            ->assertGone()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Endpoint legacy desactive. Utilisez la route API sans extension .php.');
    }

    public function test_https_middleware_redirects_http_when_enabled(): void
    {
        config(['crm.security.force_https' => true]);

        $this->get('/login')
            ->assertStatus(301)
            ->assertRedirect('https://127.0.0.1:8000/login');
    }

    public function test_hsts_header_is_sent_on_secure_requests_when_enabled(): void
    {
        config([
            'crm.security.force_https' => false,
            'crm.security.hsts_enabled' => true,
            'crm.security.hsts_max_age' => 31536000,
            'crm.security.hsts_include_subdomains' => true,
            'crm.security.hsts_preload' => true,
        ]);

        $this->withHeader('X-Forwarded-Proto', 'https')
            ->get('/login')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
    }

    public function test_security_headers_are_sent_without_forcing_csp_by_default(): void
    {
        $response = $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-XSS-Protection', '1; mode=block')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_content_security_policy_can_be_enabled_in_report_only_mode(): void
    {
        config([
            'crm.security.csp_enabled' => true,
            'crm.security.csp_report_only' => true,
            'crm.security.csp' => "default-src 'self'; frame-ancestors 'none'",
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertHeader('Content-Security-Policy-Report-Only', "default-src 'self'; frame-ancestors 'none'");
    }

    public function test_crm_api_throttle_limit_uses_authenticated_crm_role(): void
    {
        config([
            'crm.api.throttle_per_minute' => 120,
            'crm.api.role_throttle_per_minute.responsable' => 17,
        ]);

        $account = User::factory()->create();
        CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'Manager API',
            'role' => 'responsable',
            'active' => true,
        ]);

        $request = Request::create('/api/mobile/me');
        $request->setUserResolver(fn (): User => $account);

        $limit = RateLimiter::limiter('crm-api')($request);

        $this->assertSame(17, $limit->maxAttempts);
        $this->assertSame((string) $account->id, $limit->key);
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
