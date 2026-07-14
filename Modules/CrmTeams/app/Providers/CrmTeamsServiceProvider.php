<?php

namespace Modules\CrmTeams\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CrmTeamsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../../routes/web.php');
    }
}
