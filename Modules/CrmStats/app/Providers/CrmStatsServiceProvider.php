<?php

namespace Modules\CrmStats\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmStatsServiceProvider extends CrmModuleServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'crm-stats');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'crm-stats');

        $this->bootCrmModule(__DIR__.'/../..');
    }
}
