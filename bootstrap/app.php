<?php

use App\Http\Middleware\AuditLegacyPhpApi;
use App\Http\Middleware\CompressResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Modules\CrmAdministration\Console\Commands\EnsureCrmAdminCommand;
use Modules\CrmCashControl\Console\Commands\ArchiveCashReceiptsCommand;
use Modules\CrmCore\Console\Commands\BackupDatabaseCommand;
use Modules\CrmCore\Console\Commands\MonitorQueueSizeCommand;
use Modules\CrmCore\Console\Commands\PublishCrmModuleAssetsCommand;
use Modules\CrmCore\Console\Commands\RefreshDashboardMetricsCommand;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        ArchiveCashReceiptsCommand::class,
        BackupDatabaseCommand::class,
        EnsureCrmAdminCommand::class,
        MonitorQueueSizeCommand::class,
        PublishCrmModuleAssetsCommand::class,
        RefreshDashboardMetricsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'crm.compress' => CompressResponse::class,
            'crm.legacy_php_api' => AuditLegacyPhpApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
