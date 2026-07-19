<?php

namespace Modules\CrmTeams\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmTeamsServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
