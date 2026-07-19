<?php

namespace Modules\CrmCore\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\CrmCore\Events\CrmDomainEvent;
use Modules\CrmCore\Listeners\LogCrmDomainEvent;

class CrmCoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../../routes/web.php');

        Event::listen(CrmDomainEvent::class, LogCrmDomainEvent::class);
    }
}
