<?php

use App\Http\Middleware\AuditLegacyPhpApi;
use App\Http\Middleware\CompressResponse;
use App\Http\Middleware\EnforceHttpsAndHsts;
use App\Http\Middleware\EnsureCrmMobileTokenScope;
use App\Http\Middleware\EnsureCrmModuleAccess;
use App\Http\Middleware\MirrorAuthenticatedSessionMetadata;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request as HttpRequest;
use Modules\CrmAdministration\Console\Commands\EnsureCrmAdminCommand;
use Modules\CrmCashControl\Console\Commands\ArchiveCashReceiptsCommand;
use Modules\CrmCore\Console\Commands\ArchiveCrmDataCommand;
use Modules\CrmCore\Console\Commands\BackupDatabaseCommand;
use Modules\CrmCore\Console\Commands\ManageCrmFeatureFlagCommand;
use Modules\CrmCore\Console\Commands\MonitorQueueSizeCommand;
use Modules\CrmCore\Console\Commands\PublishCrmModuleAssetsCommand;
use Modules\CrmCore\Console\Commands\RefreshDashboardMetricsCommand;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        ArchiveCashReceiptsCommand::class,
        ArchiveCrmDataCommand::class,
        BackupDatabaseCommand::class,
        EnsureCrmAdminCommand::class,
        ManageCrmFeatureFlagCommand::class,
        MonitorQueueSizeCommand::class,
        PublishCrmModuleAssetsCommand::class,
        RefreshDashboardMetricsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('CRM_TRUSTED_PROXIES', ''))
        )));
        $trustedHosts = array_values(array_filter(array_map(
            static function (string $host): string {
                $host = trim($host);

                if ($host === '' || str_starts_with($host, '^')) {
                    return $host;
                }

                return '^'.str_replace('\*', '.*', preg_quote($host, '#')).'$';
            },
            explode(',', (string) env('CRM_TRUSTED_HOSTS', ''))
        )));

        $middleware->trustProxies(
            at: $trustedProxies !== [] ? $trustedProxies : null,
            headers: HttpRequest::HEADER_X_FORWARDED_FOR
                | HttpRequest::HEADER_X_FORWARDED_HOST
                | HttpRequest::HEADER_X_FORWARDED_PORT
                | HttpRequest::HEADER_X_FORWARDED_PROTO
                | HttpRequest::HEADER_X_FORWARDED_PREFIX
                | HttpRequest::HEADER_X_FORWARDED_AWS_ELB
        );
        $middleware->trustHosts(
            at: $trustedHosts !== [] ? $trustedHosts : null,
            subdomains: (bool) env('CRM_TRUSTED_HOST_SUBDOMAINS', true)
        );
        $middleware->append(EnforceHttpsAndHsts::class);
        $middleware->append(MirrorAuthenticatedSessionMetadata::class);

        $middleware->alias([
            'crm.compress' => CompressResponse::class,
            'crm.legacy_php_api' => AuditLegacyPhpApi::class,
            'crm.mobile_scope' => EnsureCrmMobileTokenScope::class,
            'crm.module' => EnsureCrmModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        if (class_exists(Integration::class)) {
            Integration::handles($exceptions);
        }
    })->create();
