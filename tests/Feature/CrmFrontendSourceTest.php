<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmFrontendSourceTest extends TestCase
{
    public function test_crm_shell_is_loaded_from_versioned_vite_source(): void
    {
        $blade = (string) file_get_contents(resource_path('views/crm.blade.php'));
        $viteConfig = (string) file_get_contents(base_path('vite.config.js'));
        $shell = (string) file_get_contents(resource_path('frontend/crm/shell.ts'));
        $nativeShell = (string) file_get_contents(resource_path('frontend/crm/layout/native-shell.ts'));
        $legacyTemplate = (string) file_get_contents(resource_path('frontend/crm/legacy/template-compat.ts'));
        $hosts = (string) file_get_contents(resource_path('frontend/crm/modules/hosts.ts'));
        $modules = (string) file_get_contents(resource_path('frontend/crm/modules/register.ts'));

        $this->assertStringContainsString("@vite(config('crm_frontend.vite_entries'))", $blade);
        $this->assertStringContainsString('id="crm-shell-config"', $blade);
        $this->assertStringContainsString('<div id="root"></div>', $blade);
        $this->assertStringContainsString('meta name="csrf-token"', $blade);
        $this->assertStringNotContainsString('legacyAdminexScript', $blade);
        $this->assertStringNotContainsString('legacyAdminexStylesheet', $blade);
        $this->assertStringNotContainsString('resources/frontend/adminex/src/main.tsx', $viteConfig);
        $this->assertStringNotContainsString('resources/frontend/adminex/src', $viteConfig);
        $this->assertStringNotContainsString('adminex-ui', $viteConfig);
        $this->assertStringNotContainsString('assets/legacy-adminex.css', $blade);

        $this->assertFileExists(resource_path('frontend/crm/shell.ts'));
        $this->assertFileExists(resource_path('frontend/crm/api/client.ts'));
        $this->assertFileExists(resource_path('frontend/crm/config.ts'));
        $this->assertFileExists(resource_path('frontend/crm/legacy/logout-bridge.ts'));
        $this->assertFileExists(resource_path('frontend/crm/layout/native-shell.ts'));
        $this->assertFileExists(resource_path('frontend/crm/loader.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/embed-bridge.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/fallback-nav.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/settings.ts'));
        $this->assertFileExists(resource_path('frontend/crm/modules/hosts.ts'));
        $this->assertFileExists(resource_path('frontend/crm/router/menu.ts'));
        $this->assertFileExists(resource_path('frontend/crm/styles/shell.css'));

        $this->assertStringContainsString('installCrmModuleHostGuard', $shell);
        $this->assertStringContainsString('installNativeCrmShell', $shell);
        $this->assertStringContainsString('installCurrentCrmModuleRouteLoader', $shell);
        $this->assertStringContainsString('loadCurrentCrmModuleOverlay', $shell);
        $this->assertStringContainsString('preloadRemainingCrmModuleOverlays', $shell);
        $this->assertStringNotContainsString('loadLegacyAdminex()', $shell);
        $this->assertStringContainsString('data-crm-native-shell', $nativeShell);
        $this->assertStringContainsString('layout-sidebar crm-native-sidebar', $nativeShell);
        $this->assertStringContainsString('layout-header crm-native-header', $nativeShell);
        $this->assertStringContainsString("fetch('/api/administration?action=profile'", $nativeShell);
        $this->assertStringContainsString('profile.navigation', $nativeShell);
        $this->assertStringContainsString('window.CRM_NAV_FALLBACK = profile.navigation', $nativeShell);
        $this->assertStringContainsString('data-crm-native-submenu-toggle', $nativeShell);
        $this->assertStringContainsString('iconForKey', $nativeShell);
        $this->assertStringContainsString("new Set(['home', 'apps', 'accounting', 'internal'])", $nativeShell);
        $this->assertStringContainsString("commercial: 'dashboard'", $nativeShell);
        $this->assertStringContainsString('isLegacyTemplateRoute()', $legacyTemplate);
        $this->assertStringContainsString('return false;', $legacyTemplate);
        $this->assertStringNotContainsString('appendModuleScript', $legacyTemplate);
        $this->assertStringNotContainsString('legacyAdminexStylesheet', $legacyTemplate);

        $this->assertStringContainsString("id: 'crm-sales-tours-module'", $hosts);
        $this->assertStringContainsString("paths: ['/rapport-visite', '/tournees-representants']", $hosts);
        $this->assertStringContainsString("id: 'crm-reservations-module'", $hosts);
        $this->assertStringContainsString("id: 'crm-equipment-rentals-module'", $hosts);
        $this->assertStringContainsString("id: 'crm-administration-module'", $hosts);
        $this->assertStringContainsString("id: 'crm-tapis-romus-module'", $hosts);
        $this->assertStringNotContainsString('adminexOnly', $hosts);
        $this->assertStringContainsString("prefix: '/documents/'", $hosts);
        $this->assertStringContainsString('refreshStaleRouteOnce', $hosts);
        $this->assertStringContainsString('refreshMissingHostOnce', $hosts);
        $this->assertStringContainsString('scheduleMissingHostRefresh', $hosts);
        $this->assertStringContainsString('clearCrmRuntimeCaches', $hosts);

        $this->assertStringContainsString("administration: () => import('../../../../Modules/CrmAdministration/resources/assets/crm-administration.js')", $modules);
        $this->assertStringContainsString("equipmentRentals: () => import('../../../../Modules/CrmEquipmentRentals/resources/assets/crm-equipment-rentals.js')", $modules);
        $this->assertStringContainsString("reservations: () => import('../../../../Modules/CrmReservations/resources/assets/crm-reservations.js')", $modules);
        $this->assertStringContainsString("tapisRomus: () => import('../../../../Modules/CrmTapisRomus/resources/assets/crm-tapis-romus.js')", $modules);
        $this->assertStringContainsString("cashControl: () => import('../../../../Modules/CrmCashControl/resources/assets/crm-controle-caisse.js')", $modules);
        $this->assertStringNotContainsString('mountLegacyReactComponent', $modules);
        $this->assertStringNotContainsString('loadLegacyAsset', $modules);
        $this->assertStringNotContainsString('transitionalReactModules', $modules);
        $this->assertStringNotContainsString('import(/* @vite-ignore */ `/assets/', $modules);

        $this->assertFileDoesNotExist(resource_path('frontend/crm/legacy/react-components.ts'));
        $this->assertDirectoryDoesNotExist(resource_path('frontend/adminex'));
        $this->assertFileDoesNotExist(resource_path('frontend/static/assets/index-CqSzWeas.js'));
        $this->assertFileDoesNotExist(resource_path('frontend/static/assets/legacy-adminex.css'));

        $mobileFallback = (string) file_get_contents(resource_path('frontend/crm/mobile/fallback-nav.ts'));
        $shellCss = (string) file_get_contents(resource_path('frontend/crm/styles/shell.css'));

        $this->assertStringContainsString("window.matchMedia('(max-width: 767.98px)')", $mobileFallback);
        $this->assertStringContainsString('shouldUseFallbackNavigation', $mobileFallback);
        $this->assertStringContainsString("document.body.classList.contains('crm-mobile-app')", $mobileFallback);
        $this->assertStringContainsString("document.body.classList.contains('crm-mobile-embed')", $mobileFallback);
        $this->assertStringContainsString('new MutationObserver', $mobileFallback);
        $this->assertStringNotContainsString("if (!document.body.classList.contains('crm-mobile-app'))", $mobileFallback);
        $this->assertStringContainsString('body.crm-mobile-fallback-nav-browser', $shellCss);
    }

    public function test_static_assets_keep_only_brand_and_pwa_files(): void
    {
        $assetsDir = resource_path('frontend/static/assets');

        $this->assertFileExists($assetsDir.'/logo/martin-sols-logo.png');
        $this->assertFileExists($assetsDir.'/logo/logomark.png');
        $this->assertFileExists($assetsDir.'/pwa/icon-192.png');
        $this->assertFileExists($assetsDir.'/pwa/icon-512.png');
        $this->assertFileDoesNotExist($assetsDir.'/logo/logo.svg');
        $this->assertFileDoesNotExist($assetsDir.'/logo/logo-dark.svg');
        $this->assertSame([], glob($assetsDir.'/*.js') ?: []);
        $this->assertSame([], glob($assetsDir.'/*.css') ?: []);
        $this->assertDirectoryDoesNotExist($assetsDir.'/products');
        $this->assertDirectoryDoesNotExist($assetsDir.'/gallery');
        $this->assertDirectoryDoesNotExist($assetsDir.'/avatars');
    }

    public function test_deployment_archive_includes_vite_build_output(): void
    {
        $script = (string) file_get_contents(base_path('scripts/deploy-planethoster.sh'));
        $gitignore = (string) file_get_contents(base_path('.gitignore'));

        $this->assertStringContainsString('tar -rf "$LOCAL_ARCHIVE_TAR" public/build', $script);
        $this->assertStringContainsString('if [ -d public/build ]; then', $script);
        $this->assertStringNotContainsString('if [ "$CRM_DEPLOY_BUILD" != "0" ] && [ -d public/build ]; then', $script);
        $this->assertStringContainsString('Manifest Vite absent: ${RELEASE_DIR}/public/build/manifest.json', $script);
        $this->assertStringContainsString('php artisan crm:publish-static-assets --force --clean', $script);
        $this->assertStringContainsString('php artisan crm:publish-module-assets --force', $script);
        $this->assertStringContainsString('gzip -c "$LOCAL_ARCHIVE_TAR" > "$LOCAL_ARCHIVE"', $script);
        $this->assertStringContainsString('RELEASES_DIR="${CRM_DEPLOY_ROOT}/releases"', $script);
        $this->assertStringContainsString('SHARED_DIR="${CRM_DEPLOY_ROOT}/shared"', $script);
        $this->assertStringContainsString('CURRENT_LINK="${CRM_DEPLOY_ROOT}/current"', $script);
        $this->assertStringContainsString('mv -Tf "$NEXT_LINK" "$CURRENT_LINK"', $script);
        $this->assertStringContainsString('rollback_current', $script);
        $this->assertStringContainsString('curl -fsS --max-time 10 "$health_url"', $script);
        $this->assertStringContainsString('php artisan horizon:terminate || true', $script);
        $this->assertStringContainsString('cleanup_old_releases', $script);
        $this->assertStringContainsString("--exclude='storage/redis'", $script);
        $this->assertStringContainsString("--exclude='storage/framework/cache'", $script);
        $this->assertStringContainsString('/public/assets', $gitignore);
        $this->assertStringContainsString('/public/modules', $gitignore);
    }

    public function test_dom_ready_sensitive_crm_modules_boot_even_when_loaded_late(): void
    {
        foreach ([
            base_path('Modules/CrmAdministration/resources/assets/crm-administration.js'),
            base_path('Modules/CrmCashControl/resources/assets/crm-controle-caisse.js'),
            base_path('Modules/CrmCore/resources/assets/crm-account-settings.js'),
            base_path('Modules/CrmCore/resources/assets/crm-active-site.js'),
            base_path('Modules/CrmCore/resources/assets/crm-dashboard.js'),
            base_path('Modules/CrmCore/resources/assets/crm-text-fixes.js'),
            base_path('Modules/CrmEquipmentRentals/resources/assets/crm-equipment-rentals.js'),
            base_path('Modules/CrmReservations/resources/assets/crm-reservations.js'),
            base_path('Modules/CrmTapisRomus/resources/assets/crm-tapis-romus.js'),
        ] as $assetPath) {
            $asset = (string) file_get_contents($assetPath);

            $this->assertStringContainsString('document.readyState ===', $asset, $assetPath);
        }
    }
}
