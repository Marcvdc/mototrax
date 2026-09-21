<?php

namespace Tests\Unit\Services\Gpx;

use App\Services\Gpx\LineSimplifier;
use App\Services\Gpx\WaypointReducer;
use PHPUnit\Framework\TestCase;

class WaypointReducerTest extends TestCase
{
    private WaypointReducer $reducer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reducer = new WaypointReducer(new LineSimplifier);
    }

    public function test_returns_no_waypoints_for_a_two_point_track(): void
    {
        $points = [['lat' => 0.0, 'lng' => 0.0], ['lat' => 1.0, 'lng' => 1.0]];

        $this->assertSame([], $this->reducer->reduce($points, 9));
    }

    public function test_returns_no_waypoints_when_max_is_zero(): void
    {
        $this->assertSame([], $this->reducer->reduce($this->windingTrack(), 0));
    }

    public function test_long_track_is_reduced_to_at_most_the_maximum(): void
    {
        $reduced = $this->reducer->reduce($this->windingTrack(), 9);

        $this->assertLessThanOrEqual(9, count($reduced));
        $this->assertNotEmpty($reduced);
    }

    public function test_start_and_end_are_not_part_of_the_waypoints(): void
    {
        $track = $this->windingTrack();

        $reduced = $this->reducer->reduce($track, 9);

        $this->assertNotContains($track[0], $reduced);
        $this->assertNotContains($track[count($track) - 1], $reduced);
    }

    public function test_keeps_the_corner_and_drops_the_straight_stretch(): void
    {
        // Een lang recht stuk met één scherpe knik. De knik moet overblijven.
        $points = [];
        for ($i = 0; $i <= 50; $i++) {
            $points[] = ['lat' => 0.0, 'lng' => $i / 10];
        }
        $corner = ['lat' => 2.0, 'lng' => 5.0];
        $points[] = $corner;
        $points[] = ['lat' => 2.0, 'lng' => 6.0];

        $reduced = $this->reducer->reduce($points, 9);

        $this->assertContains($corner, $reduced);
    }

    public function test_identical_points_do_not_cause_an_endless_loop(): void
    {
        $points = array_fill(0, 500, ['lat' => 1.0, 'lng' => 1.0]);

        $reduced = $this->reducer->reduce($points, 9);

        $this->assertLessThanOrEqual(9, count($reduced));
    }

    /**
     * @return list<array{lat: float, lng: float}>
     */
    private function windingTrack(): array
    {
        $points = [];
        for ($i = 0; $i < 400; $i++) {
            $points[] = ['lat' => sin($i / 10) * 0.5, 'lng' => $i / 100];
        }

        return $points;
    }
}
