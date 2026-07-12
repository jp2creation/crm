<?php

namespace App\Providers;

use App\Listeners\RevokeUserSanctumTokens;
use App\Models\User;
use App\Observers\UserTokenObserver;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        RateLimiter::for('crm-legacy-api', function (Request $request): Limit {
            $maxAttempts = max(1, (int) config('crm.legacy_php_api.throttle_per_minute', 60));
            $key = $request->user()?->getAuthIdentifier() ?: $request->ip();

            return Limit::perMinute($maxAttempts)->by((string) $key);
        });

        RateLimiter::for('crm-api', function (Request $request): Limit {
            $maxAttempts = max(1, (int) config('crm.api.throttle_per_minute', 120));
            $key = $request->user()?->getAuthIdentifier() ?: $request->ip();

            return Limit::perMinute($maxAttempts)->by((string) $key);
        });

        RateLimiter::for('crm-login', function (Request $request): Limit {
            $maxAttempts = max(1, (int) config('crm.login.throttle_per_minute', 10));
            $email = strtolower((string) $request->input('email', 'guest'));

            return Limit::perMinute($maxAttempts)->by($email.'|'.$request->ip());
        });

        Event::listen(PasswordReset::class, RevokeUserSanctumTokens::class);
        User::observe(UserTokenObserver::class);
    }
}
