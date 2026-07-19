<?php

namespace Modules\CrmReservations\Providers;

use Illuminate\Support\Facades\Event;
use Modules\CrmCore\Events\CrmDomainEvent;
use Modules\CrmCore\Providers\CrmModuleServiceProvider;
use Modules\CrmReservations\Listeners\NotifyReservationDeleted;

class CrmReservationsServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');

        Event::listen(CrmDomainEvent::class, NotifyReservationDeleted::class);
    }
}
