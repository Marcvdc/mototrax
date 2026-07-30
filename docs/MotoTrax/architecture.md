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

## Feed & Social flow (MVP-005)

```
POST /api/posts  (Sanctum auth)
  StorePostRequest (content, type, route_id?, maintenance_log_id?)
    ├─ Cross-field validatie: route_share → route.is_public=true; maintenance → eigendom log
    └─ PostController@store → FeedService::createPost(User $author, array $data)
          ├─ Post::create(...)
          └─ [type=route_share] FeedService::dispatchRouteShared
                  └─ Notification::send(alle andere users, RouteSharedNotification)
                         └─ ShouldQueue → database channel → notifications tabel

GET /api/feed  (Sanctum auth)
  PostController@index → FeedService::feed(int $perPage)
    └─ Post::query()->forFeed()   (scope: with user/route/maintenanceLog + latest())
         └─ paginate($perPage)   (per_page in:10,25,50 default 25)
               └─ PostResource::collection(...)
                     └─ route-privacy filter in PostResource::routePayload()
                           route=null als is_public=false én viewer ≠ eigenaar
```

### Feed-endpoints

| Methode | URI                                  | Naam                        | Auth    | Doel                                    |
|--------:|--------------------------------------|-----------------------------|---------|------------------------------------------|
| GET     | /api/feed                            | api.feed.index              | Sanctum | Gepagineerde tijdlijn (alle posts)       |
| GET     | /api/posts/{post}                    | api.posts.show              | Sanctum | Enkelvoudige post + privacy filter       |
| POST    | /api/posts                           | api.posts.store             | Sanctum | Aanmaken post (text/route_share/maint.)  |
| DELETE  | /api/posts/{post}                    | api.posts.destroy           | Sanctum | Verwijderen eigen post (PostPolicy)      |
| GET     | /api/notifications                   | api.notifications.index     | Sanctum | Ongelezen + recente notificaties (max 50)|
| POST    | /api/notifications/{id}/read         | api.notifications.read      | Sanctum | Markeer één notificatie als gelezen      |
| POST    | /api/notifications/read-all          | api.notifications.read-all  | Sanctum | Markeer alle notificaties als gelezen    |

### Privacy & Autorisatie (Feed)

- `PostPolicy::delete` → alleen eigenaar (`user_id === auth.id`).
- Andermans route_share post blijft zichtbaar in feed, maar `route`-payload wordt `null` als `route.is_public=false` en viewer ≠ eigenaar (filter in `PostResource`).
- Notificaties zijn per-user — `NotificationController` queryt altijd `$request->user()->notifications()`.

### Notificatie-payload (database channel)

```json
{
  "post_id": 42,
  "route_id": 7,
  "route_name": "Eindhoven Loop",
  "actor_id": 3,
  "actor_name": "Marc"
}
```

Zie ook [ADR 0003](adr/0003-notification-fanout-all-users.md) voor de fanout-keuze.

---

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

## Map Preview (MVP-004b)

### Datastroom

```
Route (DB)
  ├─ Filament Edit-pagina  (/admin/routes/{id}/edit)
  │   ViewComponent::make('filament.route.map-preview')
  │       └─ RouteService::toGeoJson(route) → JSON inline in Blade
  │           └─ <x-route-map> → <div data-route-map> + <script type="application/json">
  │                └─ route-map.js → Leaflet kaart, polyline + start/end markers
  │
  ├─ Filament View-pagina  (/admin/routes/{id})
  │   Identiek aan Edit — zelfde Schema component, read-only context.
  │   Create-pagina toont géén kaart (visible: operation !== 'create').
  │
  └─ Publieke web-pagina  (/routes/{id})
      RouteController@show → abort_unless($route->is_public, 404)
          └─ view('routes.show') met <x-route-map :geojson="$geojson" />
```

### Kaart-component `<x-route-map>`

Herbruikbare Blade-component (`resources/views/components/route-map.blade.php`):
- Roept `RouteService::toGeoJson()` aan als `$geojson` niet meegegeven is.
- Bij ontbreken van GPX/track: toont placeholder "Geen track beschikbaar."
- Inline JSON via `json_encode(..., JSON_HEX_TAG | JSON_HEX_AMP | ...)` — geen `{!! !!}` op user-data.
- Tile-config uit `config/map.php` als `data-*` attrs → uitgelezen door `route-map.js`.

### JavaScript bundle

`resources/js/route-map.js` (eigen Vite entry, **niet** gebundeld met `app.js`):
- Auto-discovery: initialiseert alle `[data-route-map]` elementen na `DOMContentLoaded` en na Livewire `navigated`/`update` events.
- Leest GeoJSON uit `<script type="application/json">` payload binnen het container-element.
- Rendert Leaflet `L.geoJSON` polyline (rood, weight 4) + start (groen) / eind (rood) divIcon markers.
- `map.fitBounds(trackLayer.getBounds(), { padding: [24, 24] })` voor automatische zoom op bbox.
- Bundle-size: 150 KB / **44 KB gzipped** (voldoet aan DoD < 100 KB gzipped).

### Tile provider

Geconfigureerd via `config/map.php` (env-overrideable — zie ADR 0002):
- Default: OpenStreetMap raster tiles `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`
- Switch zonder code-wijziging via `MAP_TILE_URL` + `MAP_TILE_ATTRIBUTION` + `MAP_TILE_HOSTS` env-vars.

### Endpoints (web)

| Methode | URI              | Naam         | Auth      | Doel                                        |
|--------:|------------------|--------------|-----------|---------------------------------------------|
| GET     | /routes/{route}  | routes.show  | geen      | Publieke kaartpagina (alleen is_public=true) |

### Security publieke pagina

- `abort_unless($route->is_public, 404)` — privé routes geven 404 (niet 403, geen existence-leak).
- `throttle:60,1` rate-limit per IP.
- `AddRouteMapCspHeader` middleware: `default-src 'self'`, `img-src`/`connect-src` whitelist tile-host(s), `frame-ancestors 'none'`.
- Blade output via `{{ }}` (escaped) — geen `{!! !!}` op user-controlled velden.
- GeoJSON via `JSON_HEX_TAG | JSON_HEX_AMP` — geen `</script>` injection mogelijk.

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
