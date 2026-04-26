<?php

namespace Tests\Unit\Services\Gpx;

use App\Services\Gpx\LineSimplifier;
use PHPUnit\Framework\TestCase;

class LineSimplifierTest extends TestCase
{
    private LineSimplifier $simplifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->simplifier = new LineSimplifier;
    }

    public function test_returns_input_when_fewer_than_three_points(): void
    {
        $input = [['lat' => 0.0, 'lng' => 0.0], ['lat' => 1.0, 'lng' => 1.0]];

        $this->assertSame($input, $this->simplifier->simplify($input, 0.1));
    }

    public function test_collinear_intermediate_points_are_removed(): void
    {
        $input = [
            ['lat' => 0.0, 'lng' => 0.0],
            ['lat' => 0.0, 'lng' => 1.0],
            ['lat' => 0.0, 'lng' => 2.0],
            ['lat' => 0.0, 'lng' => 3.0],
            ['lat' => 0.0, 'lng' => 4.0],
        ];

        $result = $this->simplifier->simplify($input, 0.0001);

        $this->assertCount(2, $result);
        $this->assertSame($input[0], $result[0]);
        $this->assertSame($input[4], $result[1]);
    }

    public function test_significant_deviation_is_preserved(): void
    {
        $input = [
            ['lat' => 0.0, 'lng' => 0.0],
            ['lat' => 0.5, 'lng' => 1.0], // significant deviation
            ['lat' => 0.0, 'lng' => 2.0],
        ];

        $result = $this->simplifier->simplify($input, 0.1);

        $this->assertCount(3, $result);
    }

    public function test_first_and_last_points_are_always_kept(): void
    {
        $input = [
            ['lat' => 10.0, 'lng' => 10.0],
            ['lat' => 10.0001, 'lng' => 10.0001],
            ['lat' => 10.0002, 'lng' => 10.0002],
            ['lat' => 50.0, 'lng' => 50.0],
        ];

        $result = $this->simplifier->simplify($input, 5.0);

        $this->assertSame($input[0], $result[0]);
        $this->assertSame($input[3], end($result));
    }
}
