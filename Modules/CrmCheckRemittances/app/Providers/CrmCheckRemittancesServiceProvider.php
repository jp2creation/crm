<?php

namespace Modules\CrmCheckRemittances\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmCheckRemittancesServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
