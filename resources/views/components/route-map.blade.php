@props([
    'route' => null,
    'geojson' => null,
    'height' => '420px',
])

@php
    $payload = $geojson;

    if ($payload === null && $route !== null && $route->gpx_file !== null) {
        try {
            $payload = app(\App\Services\RouteService::class)->toGeoJson($route);
        } catch (\App\Services\Gpx\InvalidGpxException $e) {
            $payload = null;
        }
    }

    $payloadJson = $payload === null
        ? null
        : json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

    $tileUrl = config('map.tile_url');
    $tileAttribution = config('map.tile_attribution');
    $tileMaxZoom = (int) config('map.tile_max_zoom');
@endphp

@if ($payload === null)
    <div class="route-map-placeholder" role="status">
        {{ __('Geen track beschikbaar.') }}
    </div>
@else
    <div
        data-route-map
        data-tile-url="{{ $tileUrl }}"
        data-tile-attribution="{{ $tileAttribution }}"
        data-tile-max-zoom="{{ $tileMaxZoom }}"
        class="route-map-container"
        style="height: {{ $height }};"
        aria-label="{{ __('Route preview') }}"
    >
        <script type="application/json">{!! $payloadJson !!}</script>
    </div>
@endif
