<?php

namespace Modules\CrmReservations\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\CrmCore\Events\CrmDomainEvent;
use Modules\CrmReservations\Listeners\NotifyReservationDeleted;

class CrmReservationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../../routes/web.php');

        Event::listen(CrmDomainEvent::class, NotifyReservationDeleted::class);
    }
}
