<?php

namespace Modules\CrmEquipmentRentals\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmEquipmentRentalsServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
