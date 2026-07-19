<?php

namespace Modules\CrmDepositRequests\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmDepositRequestsServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
