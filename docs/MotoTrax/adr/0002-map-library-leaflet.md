# ADR 0002 — Map library: Leaflet 1.x + OpenStreetMap default

- **Status:** Accepted
- **Datum:** 2026-04-26
- **Context:** MVP-004b (Map Preview voor Filament + publieke web view)

## Beslissing

We gebruiken **Leaflet 1.x** als kaart-library met **OpenStreetMap raster-tiles** als default tile-provider, geconfigureerd via `config/map.php` met env-fallbacks (`MAP_TILE_URL`, `MAP_TILE_ATTRIBUTION`, `MAP_TILE_MAX_ZOOM`, `MAP_TILE_HOSTS`).

Eén bundle (via Vite + npm) wordt hergebruikt op:
- Filament Edit-pagina
- Filament View-pagina (nieuw)
- Publieke web-pagina `/routes/{route}` (alleen voor `is_public=true`)

GeoJSON wordt **server-side ingesloten** in de Blade-output via `RouteService::toGeoJson()`. Geen browser-side fetch naar `/api/routes/{id}` — sessie-auth (Filament) en publiek-toegestane routes (web) volstaan.

## Alternatieven overwogen

1. **MapLibre GL JS + MapTiler vector-tiles**
   - **Pro:** moderne vector-rendering, rotatie, 3D, retina-quality, MapTiler heeft 100k req/maand gratis tier.
   - **Con:** ~200 KB gzipped (vs ~42 KB Leaflet), vereist account+API-key, vector-styling-overhead niet nodig voor track-preview, key-rotatie risico op publieke pagina.
2. **Mapbox GL JS**
   - **Pro:** zelfde voordelen als MapLibre + premium tiles.
   - **Con:** restrictieve TOS, vereist creditcard, paid model bij groei, niet open-source-compatible.
3. **Google Maps JS API**
   - **Pro:** rijke functionaliteit, bekend bij users.
   - **Con:** TOS verbiedt opslag/caching, vereist billing-account vanaf eerste request, vendor-lock.
4. **OpenLayers**
   - **Pro:** zeer krachtig, ondersteunt veel projecties.
   - **Con:** ~150 KB+, complexere API, overkill voor enkele polyline + 2 markers.

## Gevolgen

- **Bundle-size:** Leaflet ~42 KB gzipped + leaflet.css ~14 KB. Acceptable voor admin én publiek.
- **Geen vendor-lock:** OSM is open data; switch naar MapTiler/Mapbox is een env-wijziging.
- **CSP-impact:** `img-src` en `connect-src` whitelist op `*.tile.openstreetmap.org` (default) of de geconfigureerde host.
- **Rate limit risico OSM:** OSM heavy-usage policy adviseert eigen tile-server vanaf ~100k tiles/dag. Voor MVP/admin/lichte publieke traffic onproblematisch; switch via env zonder code-wijziging.
- **Browser-side fetch overbodig:** alle GeoJSON server-side ingesloten — geen CORS, geen extra round-trip, geen API-token in browser.

## Mitigaties / opmerkingen

- **Privacy:** publieke web-pagina rendert alleen voor `is_public=true`. Privé routes → HTTP 404 (existence-leak vermijden).
- **XSS:** Blade-output via `{{ }}`, GeoJSON via `Js::from()` of `json_encode(..., JSON_HEX_TAG | JSON_HEX_AMP)`.
- **Rate-limit:** publieke web-pagina krijgt `throttle:60,1` middleware tegen scraping.
- **Toekomst:** bij groei → switch naar MapTiler raster (drop-in via `MAP_TILE_URL`) of vector-tiles (vereist library-switch — pas overwegen bij styling-eis).
- **Geen kaart op Create-pagina:** parsing produceert pas na save de bbox; placeholder volstaat.
