<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Modules\CrmCore\Services\CrmImageStorage;
use Tests\TestCase;

class CrmImageStorageTest extends TestCase
{
    public function test_data_url_image_is_verified_reencoded_and_thumbnailed(): void
    {
        Storage::fake('public');

        $stored = app(CrmImageStorage::class)->storeDataUrl($this->crmPngDataUrl(12, 8), 'profiles');

        $this->assertStringStartsWith('/uploads/assets/uploads/profiles/', $stored['url']);
        $this->assertStringEndsWith('.webp', $stored['url']);
        $this->assertSame('image/webp', $stored['mime']);
        $this->assertSame(12, $stored['width']);
        $this->assertSame(8, $stored['height']);
        Storage::disk('public')->assertExists($stored['path']);
        Storage::disk('public')->assertExists($stored['thumbnailPath']);
    }

    public function test_data_url_must_contain_a_real_image(): void
    {
        Storage::fake('public');

        $this->expectExceptionMessage('Photo invalide');

        app(CrmImageStorage::class)->storeDataUrl('data:image/png;base64,'.base64_encode('fake png content'), 'profiles');
    }

    public function test_excessive_dimensions_are_rejected(): void
    {
        Storage::fake('public');

        $this->expectExceptionMessage('Dimensions image trop grandes');

        app(CrmImageStorage::class)->storeDataUrl($this->crmPngDataUrl(4, 4), 'profiles', null, [
            'maxWidth' => 3,
            'maxHeight' => 3,
        ]);
    }
}
