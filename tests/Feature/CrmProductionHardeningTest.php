<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrmProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_response_can_be_gzip_compressed(): void
    {
        config(['crm.compression.min_bytes' => 1]);

        $response = $this->withHeader('Accept-Encoding', 'gzip')
            ->get('/api/reservations?action=health');

        $response->assertOk();
        $response->assertHeader('Content-Encoding', 'gzip');
        $this->assertSame('{"ok":true,"mode":"mysql"}', gzdecode((string) $response->baseResponse->getContent()));
    }

    public function test_backup_command_creates_compressed_database_dump(): void
    {
        Storage::fake('local');
        config([
            'crm.backup.disk' => 'local',
            'crm.backup.path' => 'testing-backups/database',
            'crm.backup.keep' => 2,
        ]);

        $this->artisan('backup:run')
            ->assertExitCode(0);

        $files = Storage::disk('local')->files('testing-backups/database');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.sql.gz', $files[0]);

        $dump = gzdecode(Storage::disk('local')->get($files[0]));

        $this->assertIsString($dump);
        $this->assertStringContainsString('Martin Sols CRM database backup', $dump);
        $this->assertStringContainsString('crm_sites', $dump);
    }
}
