<?php

namespace Modules\CrmLeaves\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmLeavesServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
