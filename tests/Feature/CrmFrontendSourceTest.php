<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmFrontendSourceTest extends TestCase
{
    public function test_crm_shell_is_loaded_from_versioned_vite_source(): void
    {
        $blade = (string) file_get_contents(resource_path('views/crm.blade.php'));

        $this->assertStringContainsString("@vite(config('crm_frontend.vite_entries'))", $blade);
        $this->assertStringContainsString('id="crm-shell-config"', $blade);
        $this->assertStringContainsString('<div id="root"></div>', $blade);
        $this->assertStringContainsString('meta name="csrf-token"', $blade);
        $this->assertStringNotContainsString('data-crm-api-csrf', $blade);
        $this->assertStringNotContainsString('data-crm-frontend-assets', $blade);
        $this->assertStringNotContainsString('data-crm-logout-bridge', $blade);
        $this->assertStringNotContainsString('logoutToCrmLogin', $blade);
        $this->assertStringNotContainsString('history[method] =', $blade);
        $this->assertStringNotContainsString('partials.brand-morph-loader', $blade);
        $this->assertStringNotContainsString("CrmAsset::url('modules/crm-core/crm-dashboard.js')", $blade);
        $this->assertStringNotContainsString("CrmAsset::url('modules/crm-leaves/crm-conges.js')", $blade);
        $this->assertStringNotContainsString('crm-mobile-app-settings-trigger', $blade);
        $this->assertStringNotContainsString('data-crm-mobile-app-settings', $blade);
        $this->assertStringNotContainsString('data-crm-mobile-fallback-nav', $blade);
        $this->assertStringNotContainsString('window.CRM_NAV_FALLBACK =', $blade);

        $this->assertFileExists(resource_path('frontend/crm/shell.ts'));
        $this->assertFileExists(resource_path('frontend/crm/api/client.ts'));
        $this->assertFileExists(resource_path('frontend/crm/config.ts'));
        $this->assertFileExists(resource_path('frontend/crm/legacy/logout-bridge.ts'));
        $this->assertFileExists(resource_path('frontend/crm/loader.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/embed-bridge.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/fallback-nav.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/settings.ts'));
        $this->assertFileExists(resource_path('frontend/crm/modules/hosts.ts'));
        $this->assertFileExists(resource_path('frontend/crm/router/menu.ts'));
        $this->assertFileExists(resource_path('frontend/crm/styles/shell.css'));

        $shell = (string) file_get_contents(resource_path('frontend/crm/shell.ts'));
        $hosts = (string) file_get_contents(resource_path('frontend/crm/modules/hosts.ts'));
        $modules = (string) file_get_contents(resource_path('frontend/crm/modules/register.ts'));

        $this->assertStringContainsString('installCrmModuleHostGuard', $shell);
        $this->assertStringContainsString('loadCurrentCrmModuleOverlay', $shell);
        $this->assertStringContainsString('preloadRemainingCrmModuleOverlays', $shell);
        $this->assertStringContainsString('Promise.all([', $shell);
        $this->assertStringContainsString('loadLegacyAdminex(),', $shell);
        $this->assertStringContainsString('loadCurrentCrmModuleOverlay(),', $shell);
        $this->assertStringContainsString("id: 'crm-sales-tours-module'", $hosts);
        $this->assertStringContainsString("paths: ['/rapport-visite', '/tournees-representants']", $hosts);
        $this->assertStringContainsString("paths: ['/reservations', '/locations-materiel']", $hosts);
        $this->assertStringContainsString('adminexOnly: true', $hosts);
        $this->assertStringContainsString("prefix: '/documents/'", $hosts);
        $this->assertStringContainsString('refreshStaleRouteOnce', $hosts);
        $this->assertStringContainsString('installRootObserver', $hosts);
        $this->assertStringContainsString('new MutationObserver', $hosts);
        $this->assertStringContainsString('moduleKeysForCurrentPath', $modules);
        $this->assertStringContainsString('requestIdleCallback', $modules);
        $this->assertStringContainsString("cashControl: () => import('../../../../Modules/CrmCashControl/resources/assets/crm-controle-caisse.js')", $modules);
    }

    public function test_adminex_source_and_dependencies_are_available_for_migration(): void
    {
        $package = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertFileExists(resource_path('frontend/adminex/src/main.tsx'));
        $this->assertFileExists(resource_path('frontend/adminex/src/routes/index.tsx'));
        $this->assertArrayHasKey('react', $package['dependencies']);
        $this->assertArrayHasKey('react-dom', $package['dependencies']);
        $this->assertArrayHasKey('react-router', $package['dependencies']);
        $this->assertArrayHasKey('@vitejs/plugin-react', $package['devDependencies']);
        $this->assertArrayHasKey('typescript', $package['devDependencies']);
        $this->assertArrayHasKey('eslint', $package['devDependencies']);
        $this->assertArrayHasKey('prettier', $package['devDependencies']);
        $this->assertArrayHasKey('vitest', $package['devDependencies']);
    }

    public function test_deployment_archive_includes_vite_build_output(): void
    {
        $script = (string) file_get_contents(base_path('scripts/deploy-planethoster.sh'));

        $this->assertStringContainsString('tar -rf "$LOCAL_ARCHIVE_TAR" public/build', $script);
        $this->assertStringContainsString('gzip -c "$LOCAL_ARCHIVE_TAR" > "$LOCAL_ARCHIVE"', $script);
        $this->assertStringContainsString('RELEASES_DIR="${CRM_DEPLOY_ROOT}/releases"', $script);
        $this->assertStringContainsString('SHARED_DIR="${CRM_DEPLOY_ROOT}/shared"', $script);
        $this->assertStringContainsString('CURRENT_LINK="${CRM_DEPLOY_ROOT}/current"', $script);
        $this->assertStringContainsString('mv -Tf "$NEXT_LINK" "$CURRENT_LINK"', $script);
        $this->assertStringContainsString('rollback_current', $script);
        $this->assertStringContainsString('curl -fsS --max-time 10 "$health_url"', $script);
        $this->assertStringContainsString('php artisan horizon:terminate || true', $script);
        $this->assertStringContainsString('cleanup_old_releases', $script);
    }

    public function test_dom_ready_sensitive_crm_modules_boot_even_when_loaded_late(): void
    {
        foreach ([
            base_path('Modules/CrmCashControl/resources/assets/crm-controle-caisse.js'),
            base_path('Modules/CrmCore/resources/assets/crm-account-settings.js'),
            base_path('Modules/CrmCore/resources/assets/crm-active-site.js'),
            base_path('Modules/CrmCore/resources/assets/crm-dashboard.js'),
            base_path('Modules/CrmCore/resources/assets/crm-text-fixes.js'),
        ] as $assetPath) {
            $asset = (string) file_get_contents($assetPath);

            $this->assertStringContainsString('document.readyState ===', $asset, $assetPath);
        }
    }
}
