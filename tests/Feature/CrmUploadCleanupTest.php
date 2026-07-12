<?php

namespace Tests\Feature;

use App\Models\CrmEquipmentItem;
use App\Models\CrmSite;
use App\Models\CrmVehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CrmUploadCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('assets/uploads/test-cleanup'));

        parent::tearDown();
    }

    public function test_vehicle_upload_is_deleted_when_vehicle_is_hidden(): void
    {
        [$photoUrl, $photoPath] = $this->fakeUpload('vehicle.jpg');
        $site = $this->site();

        $vehicle = CrmVehicle::query()->create([
            'site_id' => $site->id,
            'name' => 'Vehicule cleanup',
            'description' => '',
            'color' => '#95002e',
            'photo_url' => $photoUrl,
            'active' => true,
        ]);

        $vehicle->forceFill(['active' => false])->save();

        $this->assertFileDoesNotExist($photoPath);
    }

    public function test_equipment_upload_is_deleted_when_equipment_is_hidden(): void
    {
        [$photoUrl, $photoPath] = $this->fakeUpload('equipment.jpg');
        $site = $this->site();

        $item = CrmEquipmentItem::query()->create([
            'site_id' => $site->id,
            'name' => 'Materiel cleanup',
            'description' => '',
            'color' => '#95002e',
            'photo_url' => $photoUrl,
            'active' => true,
        ]);

        $item->forceFill(['active' => false])->save();

        $this->assertFileDoesNotExist($photoPath);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fakeUpload(string $fileName): array
    {
        $relativePath = 'assets/uploads/test-cleanup/'.$fileName;
        $absolutePath = public_path($relativePath);

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'test image');

        return ['/'.$relativePath, $absolutePath];
    }

    private function site(): CrmSite
    {
        return CrmSite::query()->create([
            'name' => 'Cleanup Test',
            'slug' => 'cleanup-test-'.uniqid(),
            'active' => true,
            'morning_start' => '07:30:00',
            'morning_end' => '12:00:00',
            'afternoon_start' => '13:30:00',
            'afternoon_end' => '17:30:00',
        ]);
    }
}
