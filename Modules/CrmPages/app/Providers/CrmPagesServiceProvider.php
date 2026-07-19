<?php

namespace Modules\CrmPages\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmPagesServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'crm-pages');
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
