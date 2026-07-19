<?php

namespace Modules\CrmAdministration\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmAdministrationServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
