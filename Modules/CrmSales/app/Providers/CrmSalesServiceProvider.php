<?php

namespace Modules\CrmSales\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmSalesServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
