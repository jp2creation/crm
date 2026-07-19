<?php

namespace Modules\CrmTapisRomus\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmTapisRomusServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
