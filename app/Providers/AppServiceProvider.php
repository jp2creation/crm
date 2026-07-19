<?php

namespace App\Providers;

use App\Listeners\RevokeUserSanctumTokens;
use App\Models\CrmDocument;
use App\Models\User;
use App\Observers\CrmActivitylogObserver;
use App\Observers\CrmDocumentObserver;
use App\Observers\UserTokenObserver;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
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
        Model::preventLazyLoading($this->app->isLocal());

        RateLimiter::for('crm-legacy-api', function (Request $request): Limit {
            $maxAttempts = max(1, (int) config('crm.legacy_php_api.throttle_per_minute', 60));
            $key = $request->user()?->getAuthIdentifier() ?: $request->ip();

            return Limit::perMinute($maxAttempts)->by((string) $key);
        });

        RateLimiter::for('crm-api', function (Request $request): Limit {
            $maxAttempts = $this->crmApiThrottleLimit($request);
            $key = $request->user()?->getAuthIdentifier() ?: $request->ip();

            return Limit::perMinute($maxAttempts)->by((string) $key);
        });

        RateLimiter::for('crm-login', function (Request $request): Limit {
            $maxAttempts = max(1, (int) config('crm.login.throttle_per_minute', 10));
            $email = strtolower((string) $request->input('email', 'guest'));

            return Limit::perMinute($maxAttempts)->by($email.'|'.$request->ip());
        });

        Event::listen(PasswordReset::class, RevokeUserSanctumTokens::class);
        $this->registerCrmAuditObservers();
        CrmDocument::observe(CrmDocumentObserver::class);
        User::observe(UserTokenObserver::class);
    }

    private function registerCrmAuditObservers(): void
    {
        if (! config('crm.audit.enabled', true)) {
            return;
        }

        foreach ((array) config('crm.audit.models', []) as $model) {
            if (is_string($model) && class_exists($model) && method_exists($model, 'observe')) {
                $model::observe(CrmActivitylogObserver::class);
            }
        }
    }

    private function crmApiThrottleLimit(Request $request): int
    {
        $role = 'guest';
        $user = $request->user();

        if ($user instanceof User) {
            $role = (string) ($user->crmUser()->value('role') ?: 'user');
        }

        $limits = (array) config('crm.api.role_throttle_per_minute', []);
        $default = (int) config('crm.api.throttle_per_minute', 120);

        return max(1, (int) ($limits[$role] ?? $default));
    }
}
