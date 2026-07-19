<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmModuleManifestTest extends TestCase
{
    public function test_crm_modules_have_explicit_provider_order(): void
    {
        $modules = collect(glob(base_path('Modules/*/module.json')) ?: [])
            ->map(function (string $path): array {
                $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
                $manifest['path'] = $path;

                return $manifest;
            });

        $this->assertNotEmpty($modules);
        $this->assertSame($modules->count(), $modules->pluck('priority')->unique()->count());

        $byName = $modules->keyBy('name');

        $this->assertLessThan(
            $byName['CrmAdministration']['priority'],
            $byName['CrmCore']['priority'],
        );
        $this->assertLessThan(
            $byName['CrmDocuments']['priority'],
            $byName['CrmAdministration']['priority'],
        );
        $this->assertLessThan(
            $byName['CrmCheckRemittances']['priority'],
            $byName['CrmCashControl']['priority'],
        );

        foreach ($modules as $module) {
            $this->assertNotEmpty($module['providers'], $module['name'].' doit declarer au moins un provider.');

            foreach ($module['providers'] as $provider) {
                $this->assertTrue(class_exists($provider), $provider.' est introuvable.');
            }
        }

        if ($byName->has('Notification') && $byName->has('Invoice')) {
            $this->assertLessThan(
                $byName['Invoice']['priority'],
                $byName['Notification']['priority'],
            );
        }
    }
}
