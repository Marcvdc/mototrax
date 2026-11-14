<?php

namespace App\Services\Gpx;

/**
 * Reduceert een GPX-spoor tot een handvol punten die de vorm van de route
 * bewaren. Bedoeld voor doelen met een harde limiet op het aantal punten,
 * zoals de Google Maps URL-API.
 *
 * @phpstan-type LatLng array{lat: float, lng: float}
 */
class WaypointReducer
{
    /**
     * Tolerantie waarmee de reductie begint. Gelijk aan de waarde die
     * RouteService voor de kaartpreview gebruikt.
     */
    public const INITIAL_TOLERANCE = 0.0001;

    /**
     * Maximaal aantal verdubbelingen van de tolerantie. Vangnet tegen een
     * spoor dat door duplicaten of NaN-achtige waarden niet kleiner wordt.
     */
    public const MAX_ITERATIONS = 40;

    public function __construct(private readonly LineSimplifier $simplifier) {}

    /**
     * Geeft de tussenpunten terug, zonder start- en eindpunt.
     *
     * @param  list<LatLng>  $points
     * @return list<LatLng>
     */
    public function reduce(array $points, int $max): array
    {
        if ($max < 1 || count($points) < 3) {
            return [];
        }

        $simplified = $this->simplifyToAtMost($points, $max + 2);

        $intermediate = array_slice($simplified, 1, -1);

        if (count($intermediate) > $max) {
            $intermediate = $this->sampleEvenly($intermediate, $max);
        }

        return array_values($intermediate);
    }

    /**
     * @param  list<LatLng>  $points
     * @return list<LatLng>
     */
    private function simplifyToAtMost(array $points, int $target): array
    {
        $tolerance = self::INITIAL_TOLERANCE;
        $simplified = $points;

        for ($i = 0; $i < self::MAX_ITERATIONS && count($simplified) > $target; $i++) {
            $simplified = $this->simplifier->simplify($points, $tolerance);
            $tolerance *= 2;
        }

        return $simplified;
    }

    /**
     * Verdeelt $max punten gelijkmatig over de lijst in plaats van de staart
     * af te kappen, zodat de route over zijn hele lengte vertegenwoordigd blijft.
     *
     * @param  list<LatLng>  $points
     * @return list<LatLng>
     */
    private function sampleEvenly(array $points, int $max): array
    {
        $count = count($points);
        $step = ($count - 1) / ($max - 1 ?: 1);

        $sampled = [];
        for ($i = 0; $i < $max; $i++) {
            $sampled[] = $points[(int) round($i * $step)];
        }

        return array_values(array_unique($sampled, SORT_REGULAR));
    }
}
