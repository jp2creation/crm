<?php

namespace Modules\CrmPages\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CrmPagesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'crm-pages');
        Route::middleware('web')->group(__DIR__.'/../../routes/web.php');
    }
}
