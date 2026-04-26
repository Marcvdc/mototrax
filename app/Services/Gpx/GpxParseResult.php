<?php

namespace App\Services\Gpx;

/**
 * @phpstan-type Bbox array{min_lat: float, min_lng: float, max_lat: float, max_lng: float}
 * @phpstan-type LatLng array{lat: float, lng: float}
 * @phpstan-type TrackPoint array{lat: float, lng: float, ele: float|null, time: string|null}
 */
final class GpxParseResult
{
    /**
     * @param  Bbox  $bbox
     * @param  LatLng  $start
     * @param  LatLng  $end
     * @param  list<TrackPoint>  $points
     */
    public function __construct(
        public readonly float $distanceKm,
        public readonly ?int $durationMinutes,
        public readonly array $bbox,
        public readonly array $start,
        public readonly array $end,
        public readonly array $points,
        public readonly int $waypointCount,
        public readonly ?string $name,
    ) {}
}
