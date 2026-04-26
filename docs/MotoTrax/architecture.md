# MotoTrax — Architectuur

Korte referentie voor de hybride architectuur (Type C): Filament admin + Sanctum REST API.

## Lagen

- **Models** (`app/Models/`) — Eloquent records, casts, scopes en accessors. Geen business logic.
- **Services** (`app/Services/`) — orchestrators. Bevatten business logic en zorgen dat controllers dun blijven (CLAUDE.md STOP-regel #11).
- **Form Requests** (`app/Http/Requests/`) — validatie + autorisatie via policies (CLAUDE.md).
- **API Resources** (`app/Http/Resources/`) — output transformatie voor JSON responses.
- **Policies** (`app/Policies/`) — autorisatie per actie/model (auto-discovery via Laravel 13 conventie).
- **API Controllers** (`app/Http/Controllers/Api/`) — delegeren naar services + resources.
- **Filament Resources** (`app/Filament/Resources/`) — admin UI; deelt services met de API om logica niet te dupliceren.

## GPX-flow (MVP-004)

```
Upload (.gpx)
  ├─ API: POST /api/routes  (Sanctum auth)
  │   StoreRouteRequest → RouteController@store → RouteService::createFromUpload
  │       ├─ GpxParser::parseFile → GpxParseResult (distance, bbox, waypoints, …)
  │       └─ Storage::disk('local') → Route::create(parsed metadata)
  │
  └─ Filament: admin/routes/create
      FillsGpxMetadata trait roept dezelfde GpxParser aan in mutateFormDataBeforeCreate.
```

### Endpoints

| Methode | URI                          | Naam                | Auth      | Doel                                   |
|--------:|------------------------------|---------------------|-----------|----------------------------------------|
| GET     | /api/routes                  | api.routes.index    | optioneel | Public + (auth) eigen privé routes     |
| GET     | /api/routes/{route}          | api.routes.show     | optioneel | Detail + GeoJSON LineString preview    |
| GET     | /api/routes/{route}/gpx      | api.routes.gpx      | optioneel | Streamed download origineel `.gpx`     |
| POST    | /api/routes                  | api.routes.store    | Sanctum   | Upload + auto-parse                    |
| PUT     | /api/routes/{route}          | api.routes.update   | Sanctum   | Metadata bijwerken (eigenaar)          |
| DELETE  | /api/routes/{route}          | api.routes.destroy  | Sanctum   | Verwijderen + GPX uit storage          |

### Privacy

- `is_public = false` (default) → alleen eigenaar kan `show`, `download`, `update`, `delete`.
- `is_public = true` → leesacties open voor iedereen, mutaties alleen voor eigenaar.
- Autorisatie via `RoutePolicy` (`view`, `download`, `update`, `delete`).

### Storage

- Disk: `local` (private, niet via `asset()` bereikbaar).
- Directory: `gpx/`.
- Toegang loopt **altijd** via `RouteController@download` zodat de policy gerespecteerd wordt.

### Preview / GeoJSON

- `RouteService::toGeoJson(Route)` herparseert het opgeslagen bestand.
- Bij meer dan `RouteService::SIMPLIFY_THRESHOLD` (2000) punten: Douglas–Peucker simplificatie met tolerance `0.0001°` (~11 m).
- Output is een GeoJSON `Feature` met `LineString` geometry; coördinaten in `[lng, lat]` volgorde (GeoJSON-spec).

### Validatie / fallbacks

- Bestand: `mimes:gpx,xml`, max 10 MB.
- Inhoud: minstens één `<trkpt>` — anders 422 `Invalid GPX file.`.
- Distance: haversine sum over `<trkpt>` met aardstraal 6371.0088 km.
- Estimated time: prefereer `<time>`-stempels; bij ontbreken fallback op gemiddelde 60 km/u (`RouteService::DEFAULT_AVERAGE_SPEED_KMH`).
- XML wordt geladen met `LIBXML_NONET` (geen externe entiteiten / netwerktoegang) ter bescherming tegen XXE.
