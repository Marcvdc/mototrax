# MVP-004 — GPX Upload / Preview

- **Status:** APPROVED
- **Approved by:** Marcvdc — 2026-04-25
- **Parent issue:** [Marcvdc/mototrax#1](https://github.com/Marcvdc/mototrax/issues/1) (geen losse sub-issue; volgens user "valt onder #1")
- **Type:** COMPLEX (multi-file, nieuwe feature, raakt model/migratie/controller/service/Filament/tests)

## 1. Doel

Een rider kan een `.gpx` uploaden, het systeem leest de track automatisch in (afstand, tijd-schatting, bbox, start/eind, waypoint count), de route is privé/publiek instelbaar, en er is een preview-endpoint dat de track als GeoJSON levert voor kaartweergave.

## 2. Acceptance Criteria

| ID | Criterium |
|----|-----------|
| AC-1 | Upload van geldig `.gpx` berekent en persist `distance`, `estimated_time`, `bbox`, `start_lat/lng`, `end_lat/lng`, `waypoint_count` automatisch via `GpxParser`. |
| AC-2 | Validatie: alleen `.gpx`/XML, max 10 MB, ≥1 `<trkpt>`. Falen → 422 met duidelijke foutmelding. |
| AC-3 | Privacy: `is_public` boolean (default `false`). Publieke `GET /api/routes` toont alleen `is_public=true`. Eigenaar ziet eigen privé routes via authenticated request. |
| AC-4 | `GET /api/routes/{route}` levert detail + `track` GeoJSON LineString (Douglas–Peucker simplificatie bij >2000 punten). Privé route → 403 voor niet-eigenaar. |
| AC-5 | `GET /api/routes/{route}/gpx` levert origineel bestand met juiste content-type (auth + ownership/public check). |
| AC-6 | Filament admin-upload roept dezelfde `GpxParser` aan; preview-paneel toont distance/bbox/waypoints na upload. |

## 3. Architectuur (Type C / Hybrid)

### Nieuwe bestanden
- `app/Services/Gpx/GpxParser.php` — pure parser, input = filepath, output = `GpxParseResult`.
- `app/Services/Gpx/GpxParseResult.php` — readonly DTO (distance, duration, bbox, start, end, points[], waypointCount).
- `app/Services/RouteService.php` — orchestreert upload → parse → persist; `toGeoJson(Route)` met simplificatie.
- `app/Http/Requests/Api/StoreRouteRequest.php`
- `app/Http/Requests/Api/UpdateRouteRequest.php`
- `app/Http/Resources/RouteResource.php` (Eloquent API Resource — naamruimte verschilt van Filament `RouteResource`)
- `app/Policies/RoutePolicy.php` (+ register in `AuthServiceProvider`)
- `database/migrations/2026_04_25_xxxxxx_add_gpx_metadata_to_routes_table.php`
- `tests/Unit/Services/Gpx/GpxParserTest.php`
- `tests/Feature/Api/RouteUploadTest.php`
- `tests/Feature/Api/RoutePreviewTest.php`
- `tests/Fixtures/gpx/sample-track.gpx`, `tests/Fixtures/gpx/empty.gpx`

### Wijzigingen
- Migratie voegt toe: `is_public bool default false`, `bbox json nullable`, `start_lat/start_lng decimal(10,7) nullable`, `end_lat/end_lng decimal(10,7) nullable`, `waypoint_count int nullable`.
- `app/Models/Route.php` — fillable + casts uitbreiden, scope `public()`, `getGpxUrlAttribute()` naar named route i.p.v. directe asset URL.
- `app/Http/Controllers/Api/RouteController.php` — refactor: FormRequest + RouteService + RouteResource; `show` + `download` endpoints; ownership via Policy.
- `routes/api.php` — `GET /routes/{route}`, `GET /routes/{route}/gpx`, `is_public` filter in index.
- `app/Filament/Resources/RouteResource.php` — `is_public` toggle, `FileUpload::->afterStateUpdated(...)` parsed via service.
- `database/factories/RouteFactory.php` — `is_public`, fixture-pad voor gpx_file.

## 4. Beslissingen op open vragen

| Vraag | Keuze |
|-------|-------|
| Distance precisie | Bumpen naar `decimal(9,3)` km. Standaard akkoord van user. |
| GPX zonder timestamps | Estimated_time fallback op gem. snelheid 60 km/u. |
| GeoJSON simplificatie | >2000 punten → Douglas–Peucker tolerance 0.0001°. |
| Storage visibility | Filament v5 default = private. Public/private serving via controller-route (`/api/routes/{route}/gpx`), niet via directe `asset()` URL. `getGpxUrlAttribute()` wijst naar named route. |

## 5. Niet-doelen (out of scope)
- Geen kaartwidget in Filament (alleen JSON in API).
- Geen routesplitsing of waypoint-editing.
- Geen elevation-grafiek (alleen ruwe min/max optioneel).
- Geen async queue; sync parsing acceptabel voor ≤10 MB. Later eventueel `ShouldQueue`.

## 6. Test-strategie (≥80% coverage op gewijzigde delen)
- **Unit GpxParser:** geldige multi-track GPX, lege GPX, GPX zonder trkpt, malformed XML, bbox berekening, haversine accuracy ±1%.
- **Feature Upload:** auth required, validation 422, success → DB + storage + parsed velden gevuld.
- **Feature Preview:** publiek → 200 + GeoJSON, privé/anders eigenaar → 403, niet-bestaand → 404.
- **Feature Download:** auth + ownership/public check, juiste content-type.
- **Feature Filament (smoke):** create-flow met gpx-fixture.

## 7. Definition of Done
- [ ] Migratie + model + factory groen
- [ ] `GpxParser` + `RouteService` + unit tests groen
- [ ] API endpoints + FormRequests + Eloquent Resource + Policy
- [ ] Filament resource bijgewerkt
- [ ] PHPUnit suite groen (`php artisan test --compact --filter=Route`), coverage ≥80%
- [ ] `php -l` schoon, `pint` schoon
- [ ] `docs/MotoTrax/` GPX-flow gedocumenteerd
- [ ] ADR voor GpxParser keuze (regex/SimpleXML/native) onder `docs/MotoTrax/adr/`

## 8. Build-volgorde (BUILD MODE)
1. Migratie + model + factory + scope → unit-test op scope.
2. `GpxParser` + `GpxParseResult` + unit tests met fixtures.
3. `RouteService` (upload/persist + toGeoJson) + unit tests.
4. `RoutePolicy` + register.
5. FormRequests + Eloquent API Resource.
6. Refactor `RouteController` (index/store/show/update/destroy/download) + routes/api.php.
7. Feature tests (upload, preview, download, public-filter, ownership).
8. Filament resource bijwerken + smoke test.
9. Pint + `php -l` + volledige `php artisan test --compact`.
10. Docs + ADR.
11. Gefaseerde commits per logische groep, expliciete goedkeuring per commit.
