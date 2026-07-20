<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmFrontendSourceTest extends TestCase
{
    public function test_crm_shell_is_loaded_from_versioned_vite_source(): void
    {
        $blade = (string) file_get_contents(resource_path('views/crm.blade.php'));

        $this->assertStringContainsString("@vite(config('crm_frontend.vite_entries'))", $blade);
        $this->assertStringContainsString('data-crm-frontend-assets', $blade);
        $this->assertStringContainsString('meta name="csrf-token"', $blade);
        $this->assertStringNotContainsString('data-crm-api-csrf', $blade);
        $this->assertStringNotContainsString("CrmAsset::url('modules/crm-core/crm-dashboard.js')", $blade);
        $this->assertStringNotContainsString("CrmAsset::url('modules/crm-leaves/crm-conges.js')", $blade);
        $this->assertStringNotContainsString('data-crm-mobile-app-settings', $blade);
        $this->assertStringNotContainsString('data-crm-mobile-fallback-nav', $blade);
        $this->assertStringNotContainsString('window.CRM_NAV_FALLBACK =', $blade);

        $this->assertFileExists(resource_path('frontend/crm/shell.ts'));
        $this->assertFileExists(resource_path('frontend/crm/api/client.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/embed-bridge.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/fallback-nav.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/settings.ts'));
        $this->assertFileExists(resource_path('frontend/crm/router/menu.ts'));
        $this->assertFileExists(resource_path('frontend/crm/styles/shell.css'));
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
}
