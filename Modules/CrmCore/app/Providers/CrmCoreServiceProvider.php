<?php

namespace Modules\CrmCore\Providers;

use Illuminate\Support\Facades\Event;
use Modules\CrmCore\Events\CrmDomainEvent;
use Modules\CrmCore\Listeners\LogCrmDomainEvent;
use Modules\CrmCore\Services\CrmFeatureFlagService;

class CrmCoreServiceProvider extends CrmModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CrmFeatureFlagService::class);
    }

    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');

        Event::listen(CrmDomainEvent::class, LogCrmDomainEvent::class);
    }
}
