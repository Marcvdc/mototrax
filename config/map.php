<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tile provider
    |--------------------------------------------------------------------------
    |
    | URL-template volgens Leaflet conventie: {s} subdomein, {z} zoom, {x}/{y}
    | tile coordinates. Default = OpenStreetMap raster tiles. Voor MapTiler of
    | Mapbox: zet MAP_TILE_URL + MAP_TILE_ATTRIBUTION in .env zonder code-wijz.
    |
    */

    'tile_url' => env(
        'MAP_TILE_URL',
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    ),

    'tile_attribution' => env(
        'MAP_TILE_ATTRIBUTION',
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    ),

    'tile_max_zoom' => (int) env('MAP_TILE_MAX_ZOOM', 19),

    /*
    |--------------------------------------------------------------------------
    | CSP whitelist hosts
    |--------------------------------------------------------------------------
    |
    | Hosts die in img-src/connect-src moeten staan voor de tile provider.
    | Default dekt alle OSM subdomeinen via wildcard. Pas aan bij switch naar
    | MapTiler (api.maptiler.com) of Mapbox (api.mapbox.com).
    |
    */

    'tile_hosts' => array_filter(explode(',', (string) env(
        'MAP_TILE_HOSTS',
        '*.tile.openstreetmap.org',
    ))),

];
