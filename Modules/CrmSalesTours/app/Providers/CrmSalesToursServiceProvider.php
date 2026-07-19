<?php

namespace Modules\CrmSalesTours\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmSalesToursServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
