<?php

namespace App\Services\Gpx;

use SimpleXMLElement;

class GpxParser
{
    private const EARTH_RADIUS_KM = 6371.0088;

    public function parseFile(string $path): GpxParseResult
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidGpxException("GPX file not found or unreadable: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false || $contents === '') {
            throw new InvalidGpxException('GPX file is empty.');
        }

        return $this->parseString($contents);
    }

    public function parseString(string $xml): GpxParseResult
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $root = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

            if ($root === false) {
                throw new InvalidGpxException('Malformed GPX/XML.');
            }

            $trackPoints = $root->xpath('//*[local-name()="trkpt"]') ?: [];

            if ($trackPoints === []) {
                throw new InvalidGpxException('GPX must contain at least one <trkpt> element.');
            }

            $points = $this->extractPoints($trackPoints);
            $bbox = $this->computeBbox($points);
            $distanceKm = $this->computeDistanceKm($points);
            $durationMinutes = $this->computeDurationMinutes($points);
            $waypointCount = count($points);
            $name = $this->extractTrackName($root);

            return new GpxParseResult(
                distanceKm: round($distanceKm, 3),
                durationMinutes: $durationMinutes,
                bbox: $bbox,
                start: ['lat' => $points[0]['lat'], 'lng' => $points[0]['lng']],
                end: ['lat' => $points[array_key_last($points)]['lat'], 'lng' => $points[array_key_last($points)]['lng']],
                points: $points,
                waypointCount: $waypointCount,
                name: $name,
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @param  array<int, SimpleXMLElement>  $trackPoints
     * @return list<array{lat: float, lng: float, ele: float|null, time: string|null}>
     */
    private function extractPoints(array $trackPoints): array
    {
        $points = [];

        foreach ($trackPoints as $pt) {
            if (! isset($pt['lat'], $pt['lon'])) {
                throw new InvalidGpxException('Track point missing lat/lon attribute.');
            }

            $points[] = [
                'lat' => (float) $pt['lat'],
                'lng' => (float) $pt['lon'],
                'ele' => isset($pt->ele) ? (float) $pt->ele : null,
                'time' => isset($pt->time) ? (string) $pt->time : null,
            ];
        }

        return $points;
    }

    /**
     * @param  list<array{lat: float, lng: float, ele: float|null, time: string|null}>  $points
     * @return array{min_lat: float, min_lng: float, max_lat: float, max_lng: float}
     */
    private function computeBbox(array $points): array
    {
        $lats = array_column($points, 'lat');
        $lngs = array_column($points, 'lng');

        return [
            'min_lat' => min($lats),
            'min_lng' => min($lngs),
            'max_lat' => max($lats),
            'max_lng' => max($lngs),
        ];
    }

    /**
     * @param  list<array{lat: float, lng: float, ele: float|null, time: string|null}>  $points
     */
    private function computeDistanceKm(array $points): float
    {
        $total = 0.0;
        $count = count($points);

        for ($i = 1; $i < $count; $i++) {
            $total += $this->haversineKm(
                $points[$i - 1]['lat'],
                $points[$i - 1]['lng'],
                $points[$i]['lat'],
                $points[$i]['lng'],
            );
        }

        return $total;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @param  list<array{lat: float, lng: float, ele: float|null, time: string|null}>  $points
     */
    private function computeDurationMinutes(array $points): ?int
    {
        $first = $points[0]['time'] ?? null;
        $last = $points[array_key_last($points)]['time'] ?? null;

        if ($first === null || $last === null) {
            return null;
        }

        $startTs = strtotime($first);
        $endTs = strtotime($last);

        if ($startTs === false || $endTs === false || $endTs <= $startTs) {
            return null;
        }

        return (int) round(($endTs - $startTs) / 60);
    }

    private function extractTrackName(SimpleXMLElement $root): ?string
    {
        $names = $root->xpath('//*[local-name()="trk"]/*[local-name()="name"]') ?: [];

        if ($names === []) {
            return null;
        }

        $value = trim((string) $names[0]);

        return $value === '' ? null : $value;
    }
}
