<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\RouteResource\Concerns\FillsGpxMetadata;
use App\Services\RouteService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FillsGpxMetadataTest extends TestCase
{
    public function test_it_populates_parsed_fields_from_uploaded_gpx_path(): void
    {
        Storage::fake(RouteService::DISK);
        Storage::disk(RouteService::DISK)->put(
            'gpx/sample.gpx',
            file_get_contents(base_path('tests/Fixtures/gpx/sample-track.gpx')),
        );

        $page = new class
        {
            use FillsGpxMetadata;

            /** @param array<string,mixed> $data */
            public function run(array $data): array
            {
                return $this->fillGpxMetadata($data);
            }
        };

        $result = $page->run(['gpx_file' => 'gpx/sample.gpx']);

        $this->assertSame(3, $result['waypoint_count']);
        $this->assertGreaterThan(0.0, $result['distance']);
        $this->assertSame(15, $result['estimated_time']);
        $this->assertSame('Eindhoven Loop', $result['name']);
        $this->assertSame(51.4416, (float) $result['start_lat']);
        $this->assertArrayHasKey('min_lat', $result['bbox']);
    }

    public function test_it_returns_data_unchanged_when_gpx_file_missing(): void
    {
        Storage::fake(RouteService::DISK);

        $page = new class
        {
            use FillsGpxMetadata;

            /** @param array<string,mixed> $data */
            public function run(array $data): array
            {
                return $this->fillGpxMetadata($data);
            }
        };

        $result = $page->run(['gpx_file' => 'gpx/does-not-exist.gpx', 'name' => 'X']);

        $this->assertSame(['gpx_file' => 'gpx/does-not-exist.gpx', 'name' => 'X'], $result);
    }
}
