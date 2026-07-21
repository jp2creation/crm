<?php

namespace Modules\CrmDocuments\Providers;

use Modules\CrmCore\Providers\CrmModuleServiceProvider;

class CrmDocumentsServiceProvider extends CrmModuleServiceProvider
{
    public function boot(): void
    {
        $this->bootCrmModule(__DIR__.'/../..');
    }
}
