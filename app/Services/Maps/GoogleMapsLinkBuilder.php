<?php

namespace App\Services\Maps;

/**
 * Bouwt een deeplink naar de Google Maps URL-API, zodat een route zonder
 * GPX-viewer te openen en te navigeren is.
 *
 * Google berekent tussen de pins zelf een route over de weg, dus de link is
 * een benadering van het GPX-spoor en geen exacte kopie. Het originele
 * .gpx-bestand blijft beschikbaar voor wie het precieze spoor wil.
 *
 * @phpstan-type LatLng array{lat: float, lng: float}
 */
class GoogleMapsLinkBuilder
{
    public const BASE_URL = 'https://www.google.com/maps/dir/';

    /**
     * Google's URL-API staat maximaal negen tussenpunten toe. Op mobiele
     * browsers zijn het er drie; opent de link in de Google Maps-app, dan
     * geldt negen. Daarom is dit een default en geen harde constante: zakt
     * het in de praktijk door de ondergrens, dan volstaat een lagere waarde
     * bij de aanroep.
     */
    public const MAX_WAYPOINTS = 9;

    /**
     * Zes decimalen is ongeveer 0,1 meter. Meer precisie maakt de URL alleen
     * langer zonder dat Google er iets mee doet.
     */
    private const COORDINATE_PRECISION = 6;

    /**
     * @param  LatLng  $start
     * @param  LatLng  $end
     * @param  list<LatLng>  $waypoints
     */
    public function build(array $start, array $end, array $waypoints = [], int $maxWaypoints = self::MAX_WAYPOINTS): string
    {
        $query = [
            'api' => '1',
            'origin' => $this->formatPoint($start),
            'destination' => $this->formatPoint($end),
            'travelmode' => 'driving',
        ];

        $waypoints = $this->capWaypoints($waypoints, $maxWaypoints);

        if ($waypoints !== []) {
            $query['waypoints'] = implode('|', array_map(
                fn (array $point): string => $this->formatPoint($point),
                $waypoints,
            ));
        }

        return self::BASE_URL.'?'.http_build_query($query);
    }

    /**
     * Kapt niet de staart af maar verdeelt de toegestane punten over het hele
     * spoor, zodat het laatste deel van de route niet wegvalt.
     *
     * @param  list<LatLng>  $waypoints
     * @return list<LatLng>
     */
    private function capWaypoints(array $waypoints, int $max): array
    {
        if ($max < 1) {
            return [];
        }

        $count = count($waypoints);

        if ($count <= $max) {
            return array_values($waypoints);
        }

        $step = ($count - 1) / ($max - 1 ?: 1);

        $capped = [];
        for ($i = 0; $i < $max; $i++) {
            $capped[] = $waypoints[(int) round($i * $step)];
        }

        return array_values($capped);
    }

    /**
     * @param  LatLng  $point
     */
    private function formatPoint(array $point): string
    {
        return round($point['lat'], self::COORDINATE_PRECISION)
            .','
            .round($point['lng'], self::COORDINATE_PRECISION);
    }
}
