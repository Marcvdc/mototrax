<?php

namespace Tests\Unit\Services\Maps;

use App\Services\Maps\GoogleMapsLinkBuilder;
use PHPUnit\Framework\TestCase;

class GoogleMapsLinkBuilderTest extends TestCase
{
    private GoogleMapsLinkBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new GoogleMapsLinkBuilder;
    }

    public function test_builds_a_direct_link_without_waypoints(): void
    {
        $url = $this->builder->build(
            ['lat' => 51.4412, 'lng' => 5.4697],
            ['lat' => 50.8514, 'lng' => 5.691],
        );

        $this->assertStringStartsWith(GoogleMapsLinkBuilder::BASE_URL.'?', $url);
        $this->assertStringContainsString('api=1', $url);
        $this->assertStringContainsString('origin=51.4412%2C5.4697', $url);
        $this->assertStringContainsString('destination=50.8514%2C5.691', $url);
        $this->assertStringContainsString('travelmode=driving', $url);
        $this->assertStringNotContainsString('waypoints=', $url);
    }

    public function test_waypoints_are_pipe_separated_and_encoded(): void
    {
        $url = $this->builder->build(
            ['lat' => 51.0, 'lng' => 5.0],
            ['lat' => 52.0, 'lng' => 6.0],
            [['lat' => 51.5, 'lng' => 5.5], ['lat' => 51.8, 'lng' => 5.8]],
        );

        $this->assertStringContainsString('waypoints=51.5%2C5.5%7C51.8%2C5.8', $url);
    }

    public function test_coordinates_are_rounded_to_six_decimals(): void
    {
        $url = $this->builder->build(
            ['lat' => 51.123456789, 'lng' => 5.987654321],
            ['lat' => 52.0, 'lng' => 6.0],
        );

        $this->assertStringContainsString('origin=51.123457%2C5.987654', $url);
    }

    public function test_waypoints_above_the_maximum_are_capped(): void
    {
        $waypoints = [];
        for ($i = 1; $i <= 30; $i++) {
            $waypoints[] = ['lat' => 51.0 + $i / 100, 'lng' => 5.0];
        }

        $url = $this->builder->build(['lat' => 51.0, 'lng' => 5.0], ['lat' => 52.0, 'lng' => 5.0], $waypoints);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertCount(GoogleMapsLinkBuilder::MAX_WAYPOINTS, explode('|', $query['waypoints']));
    }

    public function test_capping_keeps_the_last_waypoint_so_the_tail_is_not_lost(): void
    {
        $waypoints = [];
        for ($i = 1; $i <= 30; $i++) {
            $waypoints[] = ['lat' => 51.0 + $i / 100, 'lng' => 5.0];
        }

        $url = $this->builder->build(['lat' => 51.0, 'lng' => 5.0], ['lat' => 52.0, 'lng' => 5.0], $waypoints);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $pins = explode('|', $query['waypoints']);

        $this->assertSame('51.01,5', $pins[0]);
        $this->assertSame('51.3,5', $pins[count($pins) - 1]);
    }

    public function test_a_lower_maximum_is_respected(): void
    {
        $waypoints = [
            ['lat' => 51.1, 'lng' => 5.0],
            ['lat' => 51.2, 'lng' => 5.0],
            ['lat' => 51.3, 'lng' => 5.0],
            ['lat' => 51.4, 'lng' => 5.0],
        ];

        $url = $this->builder->build(['lat' => 51.0, 'lng' => 5.0], ['lat' => 52.0, 'lng' => 5.0], $waypoints, 3);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertCount(3, explode('|', $query['waypoints']));
    }
}
