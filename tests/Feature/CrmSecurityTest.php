<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
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
