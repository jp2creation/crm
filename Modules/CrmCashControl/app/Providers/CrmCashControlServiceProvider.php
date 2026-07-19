<?php

namespace Modules\CrmCashControl\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmCashControlServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
