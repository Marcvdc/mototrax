---
name: MVP-004b — Map Preview (Filament)
description: Visuele kaart-preview van GPX-routes in Filament admin op basis van bestaande GeoJSON-output uit MVP-004
type: plan
---

# MVP-004b — Map Preview (Filament)

- **Status:** APPROVED
- **Approved by:** Marcvdc — 2026-04-26
- **Parent issue:** [Marcvdc/mototrax#1](https://github.com/Marcvdc/mototrax/issues/1) (sub van MVP-001; uitbreiding op MVP-004)
- **Type:** COMPLEX (multi-file: Filament resource + page, Blade partials, asset bundle, web-route met security)
- **Voortbouwend op:** PLAN `MVP-004-gpx-upload-preview.md` — gebruikt `RouteService::toGeoJson()` zonder wijziging. `mvp-004-gpx` is gemerged in `master` (commit `4a75b8c`).
- **Worktree:** `../mototrax-mvp-004b-map-preview` (afgesplitst van `master`, offset 2 → web 18083, db 5435).

## 1. Doel

Een ingelogde admin/gebruiker ziet in Filament op de **Edit-pagina** van een Route een interactieve kaart met de geparsede GPX-track, start/eind-marker en bbox-fit. Geen API-call vanuit de browser — GeoJSON wordt server-side ingesloten in de view zodat sessie-auth volstaat.

## 2. Acceptance Criteria

| ID | Criterium |
|----|-----------|
| AC-1 | Filament Edit-pagina van een Route met geparsede GPX toont een Leaflet-kaart met de track als polyline, start (groene marker) en eind (rode marker), automatisch ge-fit op `bbox`. |
| AC-2 | Filament **View-pagina** (nieuw toegevoegd) toont dezelfde kaart in read-only context. Create-pagina toont géén map. |
| AC-3 | Route zonder GPX/track toont op Edit én View een placeholder ("Geen track beschikbaar") i.p.v. een lege/kapotte kaart. |
| AC-4 | Publieke web-pagina `GET /routes/{route}` toont een kaart **alleen voor `is_public=true` routes**. Privé routes → 404 (niet 403, om existence niet te lekken). Geen auth vereist voor publieke routes. |
| AC-5 | GeoJSON wordt op alle drie de pagina's server-side gerenderd via `RouteService::toGeoJson(Route)`; **geen** browser-side fetch naar `/api/routes/{id}`. |
| AC-6 | Asset bundling via Vite + `npm` (`leaflet` als dep, geen CDN). Eén bundle hergebruikt op Filament-pagina's én publieke web-pagina. |
| AC-7 | Tile-provider via env-config: default OSM, optioneel `MAP_TILE_URL` + `MAP_TILE_ATTRIBUTION` voor MapTiler-fallback zonder code-wijziging. |
| AC-8 | Security: publieke web-pagina rendert geen user-controlled HTML zonder escape; CSP-headers staan `tile.openstreetmap.org` (en optioneel MapTiler) toe; geen API-tokens in client-bundle.

## 3. Architectuur

### Datastroom
```
Route (DB) ─► RouteService::toGeoJson() ─► JSON inline in Blade ─► Alpine init ─► Leaflet
```

### Nieuwe bestanden
- `resources/js/route-map.js` — initialiseert Leaflet op elk `[data-route-map]` element (auto-discovery), leest GeoJSON uit `<script type="application/json">`-payload binnen het element, leest tile-config uit een `<meta name="map-tile-*">` head-tag.
- `resources/views/components/route-map.blade.php` — herbruikbare Blade-component (`<x-route-map :route="$route" />`) die GeoJSON inlinet of placeholder rendert.
- `resources/views/routes/show.blade.php` — publieke web view met kaart voor `is_public=true` routes.
- `app/Http/Controllers/RouteController.php` (web, niet Api) — `show(Route $route)` met `abort_unless($route->is_public, 404)`.
- `app/Filament/Resources/RouteResource/Pages/ViewRoute.php` — nieuwe Filament View-page.
- `config/map.php` — `tile_url`, `tile_attribution`, `tile_max_zoom` met env-fallbacks.

### Wijzigingen
- `app/Filament/Resources/RouteResource.php` — `getPages()` aanvullen met `'view' => Pages\ViewRoute::route('/{record}')`; `infolist()` (of `form()` op Edit) krijgt een `View::make('filament.route.map-preview')` component die de `<x-route-map>` partial aanroept.
- `app/Models/Route.php` — geen wijziging (toGeoJson zit in service; bbox/track komen daar vandaan).
- `routes/web.php` — `Route::get('/routes/{route}', [RouteController::class, 'show'])->name('routes.show');`.
- `resources/js/app.js` — `import './route-map.js';`.
- `package.json` + `vite.config.js` — `leaflet` dep + `leaflet/dist/leaflet.css` import in `route-map.js`.
- `config/csp.php` of middleware — `img-src` en `connect-src` toelating voor tile-host(s).

### Tile provider — keuze: **Leaflet 1.x + OSM default, env-switchbaar**
Reden voor Leaflet boven MapLibre+MapTiler:
- Bundle-size: ~42 KB gzipped (Leaflet) vs ~200 KB (MapLibre GL).
- Zero account/key voor MVP; rendert direct.
- OSM raster-tiles voldoen voor admin + low-volume publieke previews.
- Toekomst: `MAP_TILE_URL` env-var laat ons zonder code-wijziging switchen naar MapTiler-raster (gratis tier 100k req/maand) of Mapbox als verkeer toeneemt. MapLibre vector-tiles zijn pas zinvol bij styling-eisen of 3D — niet voor track-preview.

Default config:
```
MAP_TILE_URL=https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png
MAP_TILE_ATTRIBUTION="© OpenStreetMap contributors"
MAP_TILE_MAX_ZOOM=19
```

### Security (publieke web view)
- `abort_unless($route->is_public, 404)` — geen 403 (existence-leak vermijden).
- Route-name/description door Blade-escape (`{{ }}`) — geen `{!! !!}`.
- Geen GPX-bestand direct linkbaar; download blijft via `GET /api/routes/{route}/gpx` met de bestaande policy-check.
- CSP `img-src` + `connect-src` whitelist tile-host (default OSM, env-driven).
- Rate-limit op web-route: `throttle:60,1` (per IP) tegen scraping/spam.
- Geen view counts of analytics zonder consent.

## 4. Beslissingen op open vragen

| Vraag | Beslissing | Datum |
|-------|------------|-------|
| Library | Leaflet 1.x — kleinste bundle, OSM-friendly, escape hatch via env | 2026-04-26 |
| Tile provider | OSM default; env-switchbaar naar MapTiler-raster bij groei | 2026-04-26 |
| Asset-strategie | npm + Vite, één bundle voor Filament en publieke web view | 2026-04-26 |
| Worktree-strategie | Nieuwe worktree via `worktree-setup.sh mvp-004b-map-preview <offset>` afgesplitst van `master` ná merge `mvp-004-gpx` | 2026-04-26 |
| Filament View-pagina | **Toevoegen** — read-only kaart op `/{record}` | 2026-04-26 |
| Publieke web `/routes/{id}` view | **Toevoegen** met security-eisen: 404 voor privé, escaped output, CSP, rate-limit | 2026-04-26 |

## 5. Niet-doelen
- Geen elevation-grafiek.
- Geen draggable waypoints / track editing.
- Geen offline tiles / PWA caching.
- Geen kaart op publieke (niet-Filament) pagina's.
- Geen kaart op Create-pagina (eerst save → parse → bbox vereist).

## 6. Test-strategie
- **Unit:** geen nieuwe — `RouteService::toGeoJson()` is al gedekt in `tests/Feature/Services/RouteServiceTest.php`.
- **Feature Filament:** `tests/Feature/Filament/RouteMapPreviewTest.php`
  - Edit-pagina rendert `data-route-map` met JSON-payload bevattende `geometry.coordinates`.
  - View-pagina (nieuw) rendert idem in read-only context.
  - Route zonder GPX → placeholder, geen JSON-payload.
- **Feature Web (security-zwaarpunt):** `tests/Feature/Web/RouteShowTest.php`
  - Publieke route → 200 + map-payload, geen auth nodig.
  - Privé route → **404** (niet 403).
  - Geauth eigenaar van privé route → ook 404 (publieke pagina is altijd publiek-only; eigenaar gebruikt Filament).
  - GPX-download knop niet aanwezig of vereist auth.
  - Rate-limit middleware actief (assert via `RateLimiter::tooManyAttempts` na 60 hits).
  - XSS-check: route met `<script>` in name/description rendert escaped.
- **JS smoke:** handmatige verificatie in browser bij PR-review (geen Vitest setup nu).

## 7. Definition of Done
- [ ] AC-1 t/m AC-8 groen
- [ ] PHPUnit groen (`php artisan test --compact --filter="RouteMapPreview|RouteShow"`)
- [ ] `pint` schoon, `php -l` schoon
- [ ] `npm run build` succesvol; bundle-size diff < 100 KB gzipped
- [ ] Geen browser-console errors op Edit/View/publieke pagina (manueel)
- [ ] CSP-headers geverifieerd (geen tile-fouten in console)
- [ ] ADR `docs/MotoTrax/adr/0002-map-library-leaflet.md` met motivatie Leaflet + OSM + env-fallback
- [ ] `docs/MotoTrax/architecture.md` aangevuld met Map Preview sectie (Filament + publieke web view)
- [ ] Issue #1 — sub-bullet onder MVP-004 voor map-preview afgevinkt

## 8. Build-volgorde (BUILD MODE)
1. **Pre-werk (op `master` worktree):** wacht tot `mvp-004-gpx` gemerged is. Daarna `./scripts/worktree-setup.sh mvp-004b-map-preview <offset>` op host.
2. In nieuwe worktree: `docker compose up -d` + `php artisan migrate` + `php artisan db:seed --class=RouteDemoSeeder` voor seeded test-data.
3. `config/map.php` + env-fallbacks + ADR-stub.
4. `npm install leaflet` + Vite-config check + leaflet.css import.
5. `resources/js/route-map.js` (auto-discovery, GeoJSON parse, Leaflet init) + `app.js` import + `npm run build` smoke.
6. `resources/views/components/route-map.blade.php` met inline JSON + placeholder + escape-checks.
7. Filament: `Pages\ViewRoute` + `getPages()` + map-component op Edit/View.
8. Web: `RouteController@show` + route + `routes/show.blade.php` + 404 voor privé + rate-limit + CSP.
9. Tests: `RouteMapPreviewTest` (Filament) + `RouteShowTest` (web, security-focus).
10. Manuele smoke in browser (Filament Edit/View + publieke pagina + privé → 404).
11. Pint + `php -l` + volledige `php artisan test --compact`.
12. ADR + architecture-doc update + Issue #1 checklist update.
13. Gefaseerde commits (config+ADR, deps+JS, Blade-component, Filament View-page, web-controller+view, tests, docs).

## 9. Resterende beslispunten — actie aan Marcvdc
1. **APPROVED?** Bevestig dat dit PLAN goed is — dan zet ik status op APPROVED en wacht op merge `mvp-004-gpx` voor BUILD MODE.
2. **Volgorde:** ben je akkoord dat we eerst `mvp-004-gpx` afronden+mergen (tests/lint/PR) vóór we MVP-004b worktree aanmaken? (alternatief: PLAN goedgekeurd parkeren tot mvp-004-gpx merged is — wat ik aanbeveel)
3. **Worktree-offset:** welk getal? (huidige `mvp-004-gpx` gebruikt waarschijnlijk offset 1 → poort 18082/5434; voor mvp-004b voorstel offset 2 → 18083/5435).
