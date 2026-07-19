<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmOpenApiDocumentationTest extends TestCase
{
    public function test_openapi_documents_current_crm_endpoints_and_missing_template_apis(): void
    {
        $path = resource_path('openapi/crm.yaml');

        $this->assertFileExists($path);

        $openApi = (string) file_get_contents($path);

        foreach ([
            '/api/dashboard:',
            '/api/documents:',
            '/api/documents.php:',
            '/documents/file/{document}:',
            '/api/mobile/token:',
            '/api/mobile/me:',
        ] as $route) {
            $this->assertStringContainsString($route, $openApi);
        }

        $this->assertStringContainsString('chatEndpoint: absent', $openApi);
        $this->assertStringContainsString('newsEndpoint: absent', $openApi);
        $this->assertStringContainsString('/api/chat:', $openApi);
        $this->assertStringContainsString('/api/news:', $openApi);
    }
}
