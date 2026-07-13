<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmLegacyTemplateRouteTest extends TestCase
{
    public function test_legacy_template_pages_are_not_available(): void
    {
        foreach (['/auth/login', '/dashboard', '/forms/layout', '/tables/simple', '/charts/line', '/pages/pricing'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }
}
