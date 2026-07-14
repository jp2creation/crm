<?php

namespace Modules\CrmCheckRemittances\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CrmCheckRemittancesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../../routes/web.php');
    }
}
