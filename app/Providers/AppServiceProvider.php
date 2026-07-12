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

        Event::listen(PasswordReset::class, RevokeUserSanctumTokens::class);
        User::observe(UserTokenObserver::class);
    }
}
