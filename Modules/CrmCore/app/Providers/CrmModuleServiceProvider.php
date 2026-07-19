<?php

namespace Modules\CrmCore\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

abstract class CrmModuleServiceProvider extends ServiceProvider
{
    protected function bootCrmModule(string $moduleRoot): void
    {
        $routesPath = $moduleRoot.'/routes/web.php';
        if (is_file($routesPath)) {
            Route::middleware('web')->group($routesPath);
        }

        $migrationPath = $moduleRoot.'/database/migrations';
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }
}
