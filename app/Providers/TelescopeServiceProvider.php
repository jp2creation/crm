<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Throwable;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');
        $canStoreEntries = $this->canStoreTelescopeEntries();

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal, $canStoreEntries): bool {
            if (! $canStoreEntries) {
                return false;
            }

            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    private function canStoreTelescopeEntries(): bool
    {
        if (config('telescope.driver') !== 'database') {
            return true;
        }

        try {
            return Schema::hasTable('telescope_entries')
                && Schema::hasTable('telescope_entries_tags');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        Telescope::hideRequestParameters([
            '_token',
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
        ]);

        Telescope::hideRequestHeaders([
            'authorization',
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (User $user) {
            $allowedEmails = (array) config('crm.monitoring.telescope_emails', []);

            return in_array($user->email, $allowedEmails, true);
        });
    }
}
