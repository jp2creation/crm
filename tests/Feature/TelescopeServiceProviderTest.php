<?php

namespace Tests\Feature;

use App\Providers\TelescopeServiceProvider;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class TelescopeServiceProviderTest extends TestCase
{
    public function test_telescope_does_not_record_when_storage_tables_are_missing(): void
    {
        config(['telescope.driver' => 'database']);
        Schema::dropIfExists('telescope_entries_tags');
        Schema::dropIfExists('telescope_entries');

        $provider = new TelescopeServiceProvider($this->app);
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('canStoreTelescopeEntries');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($provider));
    }
}
