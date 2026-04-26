<?php

namespace App\Services\Gpx;

class LineSimplifier
{
    /**
     * Douglas–Peucker simplification on a list of {lat, lng} points.
     *
     * @param  list<array{lat: float, lng: float}>  $points
     * @return list<array{lat: float, lng: float}>
     */
    public function simplify(array $points, float $tolerance): array
    {
        $count = count($points);

        if ($count < 3 || $tolerance <= 0.0) {
            return $points;
        }

        $keep = array_fill(0, $count, false);
        $keep[0] = true;
        $keep[$count - 1] = true;

        $this->walk($points, 0, $count - 1, $tolerance, $keep);

        $simplified = [];
        foreach ($points as $i => $p) {
            if ($keep[$i]) {
                $simplified[] = $p;
            }
        }

        return $simplified;
    }

    /**
     * @param  list<array{lat: float, lng: float}>  $points
     * @param  array<int, bool>  $keep
     */
    private function walk(array $points, int $startIndex, int $endIndex, float $tolerance, array &$keep): void
    {
        if ($endIndex - $startIndex < 2) {
            return;
        }

        $maxDistance = 0.0;
        $maxIndex = $startIndex;

        for ($i = $startIndex + 1; $i < $endIndex; $i++) {
            $d = $this->perpendicularDistance($points[$i], $points[$startIndex], $points[$endIndex]);

            if ($d > $maxDistance) {
                $maxDistance = $d;
                $maxIndex = $i;
            }
        }

        if ($maxDistance > $tolerance) {
            $keep[$maxIndex] = true;
            $this->walk($points, $startIndex, $maxIndex, $tolerance, $keep);
            $this->walk($points, $maxIndex, $endIndex, $tolerance, $keep);
        }
    }

    /**
     * @param  array{lat: float, lng: float}  $point
     * @param  array{lat: float, lng: float}  $lineStart
     * @param  array{lat: float, lng: float}  $lineEnd
     */
    private function perpendicularDistance(array $point, array $lineStart, array $lineEnd): float
    {
        $x = $point['lng'];
        $y = $point['lat'];
        $x1 = $lineStart['lng'];
        $y1 = $lineStart['lat'];
        $x2 = $lineEnd['lng'];
        $y2 = $lineEnd['lat'];

        $dx = $x2 - $x1;
        $dy = $y2 - $y1;

        if ($dx === 0.0 && $dy === 0.0) {
            return sqrt(($x - $x1) ** 2 + ($y - $y1) ** 2);
        }

        $numerator = abs($dy * $x - $dx * $y + $x2 * $y1 - $y2 * $x1);
        $denominator = sqrt($dx ** 2 + $dy ** 2);

        return $numerator / $denominator;
    }
}
