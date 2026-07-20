<?php

namespace Tests\Feature;

use App\Models\CrmModule;
use App\Models\CrmPermission;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\CrmVehicle;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use Modules\CrmCore\Services\CrmActivityLogger;
use Modules\CrmCore\Support\CrmReferenceCache;
use Spatie\Permission\Models\Role;
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

        $errors = session('errors');
        $this->assertInstanceOf(ViewErrorBag::class, $errors);
        $this->assertStringContainsString(
            'Trop de tentatives.',
            $errors->first('email'),
        );
    }

    public function test_login_remember_me_sets_the_laravel_recaller_cookie(): void
    {
        RateLimiter::clear('remember@example.test|127.0.0.1');

        $user = User::factory()->create([
            'email' => 'remember@example.test',
        ]);
        $guard = Auth::guard('web');
        $this->assertInstanceOf(SessionGuard::class, $guard);

        $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'remember' => '1',
            ])
            ->assertRedirect('/')
            ->assertCookie($guard->getRecallerName());
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

        $this->get('https://127.0.0.1:8000/login')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
    }

    public function test_security_headers_are_sent_when_csp_is_disabled(): void
    {
        config(['crm.security.csp_enabled' => false]);

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

    public function test_cors_is_delegated_to_laravel_configuration(): void
    {
        config(['cors.allowed_origins' => ['https://crm.jp2.fr', 'capacitor://localhost']]);

        $this->withHeader('Origin', 'https://crm.jp2.fr')
            ->getJson('/api/reservations?action=health')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://crm.jp2.fr');

        $blocked = $this->withHeader('Origin', 'https://evil.example')
            ->getJson('/api/reservations?action=health')
            ->assertOk();

        $this->assertFalse($blocked->headers->has('Access-Control-Allow-Origin'));

        foreach ([
            app_path('Http/Controllers/Controller.php'),
            app_path('Http/Middleware/AuditLegacyPhpApi.php'),
            base_path('Modules/CrmCore/app/Http/Requests/CrmApiRequest.php'),
        ] as $file) {
            $this->assertStringNotContainsString('Access-Control-Allow-Origin', (string) file_get_contents($file));
        }
    }

    public function test_https_proxy_and_host_trust_are_delegated_to_laravel(): void
    {
        $bootstrap = (string) file_get_contents(base_path('bootstrap/app.php'));
        $middleware = (string) file_get_contents(app_path('Http/Middleware/EnforceHttpsAndHsts.php'));

        $this->assertStringContainsString('trustProxies', $bootstrap);
        $this->assertStringContainsString('trustHosts', $bootstrap);
        $this->assertStringContainsString('$request->secure()', $middleware);
        $this->assertStringNotContainsString('X-Forwarded-Proto', $middleware);
        $this->assertStringNotContainsString('X-Forwarded-Ssl', $middleware);
    }

    public function test_default_csp_policy_is_report_only_ready_without_unsafe_eval(): void
    {
        $crmConfig = (string) file_get_contents(config_path('crm.php'));
        $envExample = (string) file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString("'csp_report_only' => env('CRM_SECURITY_CSP_REPORT_ONLY', true)", $crmConfig);
        $this->assertStringContainsString('CRM_SECURITY_CSP_ENABLED=true', $envExample);
        $this->assertStringNotContainsString("'unsafe-eval'", $crmConfig);
        $this->assertStringNotContainsString("'unsafe-eval'", $envExample);
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

    public function test_module_pages_require_matching_crm_permissions(): void
    {
        $account = User::factory()->create();
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'Sans Reservation',
            'role' => 'user',
            'active' => true,
        ]);

        $this->actingAs($account)
            ->get('/reservations')
            ->assertForbidden();

        $site = CrmSite::query()->create([
            'name' => 'Palissy Securite',
            'slug' => 'palissy-securite',
            'active' => true,
        ]);
        $module = CrmModule::query()->create([
            'name' => 'Reservations',
            'slug' => 'reservations',
            'description' => 'Reservations vehicules',
            'route_path' => '/reservations',
            'active' => true,
            'sort_order' => 10,
        ]);
        $permission = CrmPermission::query()->create([
            'name' => 'reservations.view',
            'label' => 'Voir les reservations',
            'group_label' => 'Reservations',
            'sort_order' => 10,
        ]);

        $crmUser->sites()->sync([$site->id => ['is_default' => true]]);
        $crmUser->modules()->sync([$module->id]);
        $crmUser->permissions()->sync([$permission->id]);
        CrmReferenceCache::forgetSites();
        CrmReferenceCache::forgetModules();
        CrmReferenceCache::forgetPermissions();

        $this->actingAs($account)
            ->get('/reservations')
            ->assertOk();
    }

    public function test_spatie_platform_admin_role_bypasses_crm_module_middleware(): void
    {
        $account = User::factory()->create();

        Role::query()->create(['name' => 'admin', 'guard_name' => 'web']);
        $account->assignRole('admin');

        $this->actingAs($account)
            ->get('/reservations')
            ->assertOk();
    }

    public function test_filament_resources_explicitly_delegate_to_policy_authorization(): void
    {
        $resourceFiles = collect([
            ...File::allFiles(app_path('Filament/Resources')),
            ...File::allFiles(base_path('Modules')),
        ])
            ->filter(fn ($file): bool => str_ends_with($file->getFilename(), 'Resource.php')
                && str_contains(str_replace('\\', '/', $file->getPathname()), '/Filament/Resources/'))
            ->values();

        $this->assertNotEmpty($resourceFiles);

        foreach ($resourceFiles as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            $this->assertStringContainsString('AuthorizesResourceWithPolicy', $contents, $file->getPathname());
            $this->assertStringContainsString('use AuthorizesResourceWithPolicy;', $contents, $file->getPathname());
        }

        $crmUserResource = (string) file_get_contents(base_path('Modules/CrmAdministration/app/Filament/Resources/CrmUsers/CrmUserResource.php'));

        $this->assertStringContainsString("Action::make('createLaravelAccount')", $crmUserResource);
        $this->assertStringContainsString("->authorize('update')", $crmUserResource);
    }

    public function test_crm_api_controllers_delegate_business_authorization_to_services_or_policies(): void
    {
        $controllerFiles = collect(File::allFiles(base_path('Modules')))
            ->filter(fn ($file): bool => str_contains(str_replace('\\', '/', $file->getPathname()), '/app/Http/Controllers/')
                && str_ends_with($file->getFilename(), 'Controller.php'))
            ->values();

        $this->assertNotEmpty($controllerFiles);

        foreach ($controllerFiles as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            $this->assertStringNotContainsString('CrmAccessService', $contents, $file->getPathname());
            $this->assertStringNotContainsString('hasAnyRole(', $contents, $file->getPathname());
            $this->assertStringNotContainsString('->role ===', $contents, $file->getPathname());
        }
    }

    public function test_crm_activity_logs_include_sanitized_request_context(): void
    {
        $account = User::factory()->create();
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'Audit Context',
            'role' => 'admin',
            'active' => true,
        ]);

        Route::post('/_tests/crm-activity-log', function (CrmActivityLogger $logger) use ($crmUser) {
            $logger->log($crmUser, 'test action sensible', 'details test', [
                'changes' => [
                    'name' => ['old' => 'Avant', 'new' => 'Apres'],
                    'api_token' => 'secret-token',
                ],
            ]);

            return response()->json(['ok' => true]);
        })->name('tests.crm-activity-log');

        $this->actingAs($account)
            ->withHeader('User-Agent', 'CRM Security Test Browser')
            ->postJson('/_tests/crm-activity-log', [
                'name' => 'Apres',
                'password' => 'super-secret',
                'notes' => str_repeat('x', 520),
            ])
            ->assertOk();

        $log = DB::table('crm_logs')->where('action', 'test action sensible')->first();

        $this->assertNotNull($log);
        $this->assertSame('127.0.0.1', $log->ip);
        $this->assertSame('CRM Security Test Browser', $log->user_agent);

        $context = json_decode((string) $log->context, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('/_tests/crm-activity-log', $context['request']['path']);
        $this->assertSame('tests.crm-activity-log', $context['request']['route']);
        $this->assertSame('[filtered]', $context['payload']['password']);
        $this->assertSame('[filtered]', $context['changes']['api_token']);
        $this->assertSame('Apres', $context['changes']['name']['new']);
        $this->assertStringEndsWith('...', $context['payload']['notes']);
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

    public function test_session_api_mutation_requires_csrf_token(): void
    {
        [$account, , , $vehicle] = $this->createReservationCrmUser(['reservations.create']);
        $this->enforceCsrfDuringFeatureTest();

        $this->actingAs($account)
            ->postJson('/api/reservations?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-18T08:00',
                'endAt' => '2026-08-18T08:30',
                'title' => 'Sans CSRF',
            ])
            ->assertStatus(419);

        $this->assertDatabaseMissing('crm_reservations', [
            'title' => 'Sans CSRF',
        ]);
    }

    public function test_session_api_mutation_accepts_valid_csrf_token(): void
    {
        [$account, , , $vehicle] = $this->createReservationCrmUser(['reservations.create']);
        $this->enforceCsrfDuringFeatureTest();

        $this->actingAs($account)
            ->withSession(['_token' => 'crm-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'crm-csrf-token')
            ->postJson('/api/reservations?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-18T08:00',
                'endAt' => '2026-08-18T08:30',
                'title' => 'Avec CSRF',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reservation.title', 'Avec CSRF');
    }

    public function test_mobile_bearer_api_mutation_does_not_require_csrf(): void
    {
        [$account, , , $vehicle] = $this->createReservationCrmUser(['reservations.create']);

        $token = $account->createToken('Mobile Security Test', [
            'crm:mobile',
            'crm:module:reservations',
        ])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/reservations?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-19T08:00',
                'endAt' => '2026-08-19T08:30',
                'title' => 'Mobile sans CSRF',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reservation.title', 'Mobile sans CSRF');
    }

    public function test_mobile_api_rejects_session_without_bearer_token(): void
    {
        [$account, , , $vehicle] = $this->createReservationCrmUser(['reservations.create']);

        $this->actingAs($account)
            ->postJson('/api/mobile/reservations?action=create_reservation', [
                'vehicleId' => $vehicle->id,
                'startAt' => '2026-08-20T08:00',
                'endAt' => '2026-08-20T08:30',
                'title' => 'Session sur mobile',
            ])
            ->assertUnauthorized()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Bearer Token mobile requis.');
    }

    private function enforceCsrfDuringFeatureTest(): void
    {
        $this->app->bind(ValidateCsrfToken::class, fn ($app): ValidateCsrfToken => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });

        $this->app->bind(VerifyCsrfToken::class, fn ($app): VerifyCsrfToken => new class($app, $app['encrypter']) extends VerifyCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array{0: User, 1: CrmUser, 2: CrmSite, 3: CrmVehicle}
     */
    private function createReservationCrmUser(array $permissions): array
    {
        $account = User::factory()->create();
        $crmUser = CrmUser::query()->create([
            'user_id' => $account->id,
            'name' => 'Security Reservation User '.$account->id,
            'role' => 'user',
            'active' => true,
        ]);

        $site = CrmSite::query()->create([
            'name' => 'Palissy Securite API',
            'slug' => 'palissy-securite-api',
            'active' => true,
            'morning_start' => '07:30:00',
            'morning_end' => '12:00:00',
            'afternoon_start' => '13:30:00',
            'afternoon_end' => '17:30:00',
        ]);

        $vehicle = CrmVehicle::query()->create([
            'site_id' => $site->id,
            'name' => 'Sprinter Securite',
            'description' => 'Vehicule test securite',
            'color' => '#95002e',
            'active' => true,
        ]);

        $module = CrmModule::query()->create([
            'name' => 'Reservations',
            'slug' => 'reservations',
            'description' => 'Reservations vehicules',
            'route_path' => '/reservations',
            'active' => true,
            'sort_order' => 10,
        ]);

        $permissionIds = [];

        foreach ($permissions as $index => $permission) {
            $permissionIds[] = CrmPermission::query()->create([
                'name' => $permission,
                'label' => $permission,
                'group_label' => 'Reservations',
                'sort_order' => 100 + $index,
            ])->id;
        }

        $crmUser->sites()->sync([$site->id => ['is_default' => true]]);
        $crmUser->modules()->sync([$module->id]);
        $crmUser->permissions()->sync($permissionIds);

        return [$account, $crmUser, $site, $vehicle];
    }
}
