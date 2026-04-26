<?php

namespace Tests\Unit\Services\Gpx;

use App\Services\Gpx\GpxParser;
use App\Services\Gpx\InvalidGpxException;
use PHPUnit\Framework\TestCase;

class GpxParserTest extends TestCase
{
    private GpxParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new GpxParser;
    }

    public function test_it_parses_a_valid_gpx_file_with_timestamps(): void
    {
        $result = $this->parser->parseFile($this->fixture('sample-track.gpx'));

        $this->assertSame('Eindhoven Loop', $result->name);
        $this->assertSame(3, $result->waypointCount);
        $this->assertSame(15, $result->durationMinutes);
        $this->assertGreaterThan(0, $result->distanceKm);
        $this->assertEqualsWithDelta(51.4416, $result->start['lat'], 0.0001);
        $this->assertEqualsWithDelta(5.5000, $result->end['lng'], 0.0001);
        $this->assertSame(51.4416, $result->bbox['min_lat']);
        $this->assertSame(51.46, $result->bbox['max_lat']);
    }

    public function test_haversine_accuracy_matches_expected_within_one_percent(): void
    {
        // Two points exactly 1 degree apart in longitude at the equator ≈ 111.319 km.
        $result = $this->parser->parseFile($this->fixture('no-time.gpx'));

        $this->assertEqualsWithDelta(111.319, $result->distanceKm, 1.13); // ±1%
        $this->assertNull($result->durationMinutes);
        $this->assertNull($result->name);
    }

    public function test_it_throws_when_gpx_has_no_trkpt(): void
    {
        $this->expectException(InvalidGpxException::class);
        $this->expectExceptionMessageMatches('/at least one <trkpt>/');

        $this->parser->parseFile($this->fixture('empty.gpx'));
    }

    public function test_it_throws_on_malformed_xml(): void
    {
        $this->expectException(InvalidGpxException::class);
        $this->expectExceptionMessageMatches('/Malformed/');

        $this->parser->parseFile($this->fixture('malformed.gpx'));
    }

    public function test_it_throws_when_file_is_missing(): void
    {
        $this->expectException(InvalidGpxException::class);
        $this->parser->parseFile('/nonexistent/path/to/track.gpx');
    }

    public function test_bbox_is_correct_for_known_points(): void
    {
        $result = $this->parser->parseFile($this->fixture('sample-track.gpx'));

        $this->assertSame(51.4416, $result->bbox['min_lat']);
        $this->assertSame(51.46, $result->bbox['max_lat']);
        $this->assertSame(5.4697, $result->bbox['min_lng']);
        $this->assertSame(5.5, $result->bbox['max_lng']);
    }

    private function fixture(string $name): string
    {
        return __DIR__.'/../../../Fixtures/gpx/'.$name;
    }
}
