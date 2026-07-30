---
name: MVP-005 — Feed & Social
description: PLAN voor sociale feed (posts timeline + route-share notificaties) — status APPROVED, klaar voor BUILD.
type: plan
---

# MVP-005 — Feed & Social

- **Status:** APPROVED
- **Approved by:** Marcvdc — 2026-04-26
- **Author:** Claude (Opus 4.7) — 2026-04-26
- **Parent issue:** [Marcvdc/mototrax#1](https://github.com/Marcvdc/mototrax/issues/1) (sub-scope "Feed & Social", geen losse sub-issue)
- **Type:** COMPLEX (nieuwe feature, multi-file: model/migratie/API/notifications/Filament/tests)
- **Depends on:** MVP-004 (Routes met `is_public`) — gemerged in master (commit 4a75b8c).

## 1. Doel

Een rider kan in zijn timeline berichten plaatsen (vrije tekst, route-share of maintenance-share), de feed ophalen via API met paginatie, en wanneer iemand een **publieke** route deelt krijgen alle volgers (in MVP: alle andere actieve users) een notificatie "X deelde route Y".

Scope blijft bewust klein:
- Geen follower-grafiek, geen likes/comments-feature (kolommen zijn er al, blijven leeg/0 in MVP).
- Geen real-time push (database notification channel, optioneel later mail/broadcast).
- Geen Filament-frontend voor consumenten; admin-CRUD blijft beschikbaar voor moderatie.

## 2. Acceptance Criteria

| ID | Criterium |
|----|-----------|
| AC-1 | `POST /api/posts` (auth:sanctum) maakt post aan met `type ∈ {text, route_share, maintenance}`. Validatie: `content` vereist (max 2000 tekens); bij `type=route_share` is `route_id` verplicht en de route moet `is_public=true` zijn (eigendom van auteur niet vereist — andermans publieke route delen mag, conform beslissing sectie 4); bij `type=maintenance` is `maintenance_log_id` verplicht en moet eigendom van auteur zijn. Falen → 422. |
| AC-2 | `GET /api/feed` (auth:sanctum) levert gepagineerde lijst via Laravel `paginate()` (JSON met `data`, `links`, `meta`), `per_page` gevalideerd `in:10,25,50` (default 25, conform Filament v5 page-size opties), gesorteerd op `created_at desc`, eager-loaded met `user`, `route`, `maintenanceLog`. Filtert privé-routes uit `route_share` posts (route blijft tonen alleen als `route.is_public=true OR route.user_id=auth.id`). |
| AC-3 | `GET /api/posts/{post}` (auth:sanctum) levert één post via `PostResource`; respecteert dezelfde route-privacy regel als AC-2 (privé route van iemand anders → `route` veld `null`, post zelf blijft zichtbaar). |
| AC-4 | `DELETE /api/posts/{post}` (auth:sanctum) verwijdert alleen eigen posts via `PostPolicy`; vreemde post → 403. |
| AC-5 | Bij succesvol aanmaken van een `route_share`-post wordt een `RouteSharedNotification` (database channel) aan **alle andere users** verzonden (MVP-fanout). Notificatie-payload: `post_id`, `route_id`, `route_name`, `actor_id`, `actor_name`. Verzending via `ShouldQueue` zodat de API-respons niet blokkeert. |
| AC-6 | `GET /api/notifications` (auth:sanctum) levert ongelezen + recente gelezen notificaties (laatste 50, gesorteerd desc) van de huidige user via `NotificationResource`. `POST /api/notifications/{id}/read` markeert één als gelezen; `POST /api/notifications/read-all` markeert alle als gelezen. |
| AC-7 | Filament `PostResource` toont relaties netjes (al gedaan), nieuwe filter "type" werkt; `notifications` zijn niet Filament-managed in MVP (out of scope). |

## 3. Architectuur (Type C / Hybrid)

### Nieuwe bestanden
- `app/Http/Controllers/Api/PostController.php` — `index` (= feed), `show`, `store`, `destroy`.
- `app/Http/Controllers/Api/NotificationController.php` — `index`, `markRead`, `markAllRead`.
- `app/Http/Requests/Api/StorePostRequest.php`
- `app/Http/Resources/PostResource.php` (Eloquent API Resource — naamruimte verschilt van Filament `PostResource`).
- `app/Http/Resources/NotificationResource.php`
- `app/Policies/PostPolicy.php` (+ register in `AuthServiceProvider` — aanmaken indien nog niet aanwezig, anders inhaken in `bootstrap/app.php` / `AppServiceProvider`).
- `app/Services/FeedService.php` — `feed(User $viewer, int $perPage): LengthAwarePaginator`, `createPost(User $author, array $data): Post` (incl. notification dispatch).
- `app/Notifications/RouteSharedNotification.php` — `ShouldQueue`, channel `database`.
- `database/migrations/2026_04_26_xxxxxx_create_notifications_table.php` — via `php artisan notifications:table` (Laravel 13 standaard).
- `tests/Feature/Api/Feed/FeedIndexTest.php`
- `tests/Feature/Api/Feed/PostStoreTest.php`
- `tests/Feature/Api/Feed/PostShowTest.php`
- `tests/Feature/Api/Feed/PostDestroyTest.php`
- `tests/Feature/Api/Notifications/NotificationFlowTest.php`
- `tests/Unit/Services/FeedServiceTest.php`

### Wijzigingen
- `app/Models/Post.php` — `scopeForFeed(Builder $q)` (publieke filtering helper), houden van bestaande accessors.
- `app/Models/User.php` — geen schemawijziging; `Notifiable` zit al in trait-set.
- `routes/api.php` — toevoegen onder `auth:sanctum` group:
  - `GET    /feed`              → `PostController@index`
  - `GET    /posts/{post}`      → `PostController@show`
  - `POST   /posts`             → `PostController@store`
  - `DELETE /posts/{post}`      → `PostController@destroy`
  - `GET    /notifications`             → `NotificationController@index`
  - `POST   /notifications/{id}/read`   → `NotificationController@markRead`
  - `POST   /notifications/read-all`    → `NotificationController@markAllRead`
- `database/factories/PostFactory.php` — voeg `text()`, `routeShare(Route $r)`, `maintenance(MaintenanceLog $m)` state-methodes toe; defaults consistent maken (geen `pluck` op DB die in tests leeg kan zijn).
- `app/Filament/Resources/PostResource.php` — bestaande filter "type" laten staan; geen functionele uitbreiding nodig voor MVP (alleen lichte cleanup als pint dat eist).

## 4. Beslissingen op open vragen (alle APPROVED — Marcvdc 2026-04-26)

| Vraag | Beslissing |
|-------|------------|
| Notification fanout target | **Alle andere `users`** (geen followers-tabel in MVP). |
| Notification kanalen | **`database` only** met `ShouldQueue`; mail/broadcast later. |
| Privé-routes in feed | **Post blijft zichtbaar, `route` payload wordt `null`** voor niet-eigenaar. |
| Likes/comments endpoints | **Out of scope** — kolommen blijven 0. |
| Pagination shape | **Standaard Laravel `paginate()`** JSON (`data`, `links`, `meta`). `per_page` gevalideerd `in:10,25,50`, default `25` (overeenkomstig Filament v5 page-size opties). |
| `route_share` voor andermans publieke route | **Toegestaan** — delen mag altijd zolang route publiek is. |

## 5. Niet-doelen (out of scope voor MVP-005)

- Volgrelaties (`followers`/`following`-tabel).
- Likes en comments endpoints (kolommen blijven idle).
- Real-time delivery (broadcast/Pusher/Reverb).
- Frontend timeline (alleen API + Filament-admin).
- E-mail digests of push-notificaties.
- Post-edit endpoint (alleen create/show/delete in API).

## 6. Test-strategie (≥80% coverage op gewijzigde delen)

**Unit (`FeedServiceTest`):**
- `createPost` met `type=text` → geen notificatie verstuurd.
- `createPost` met `type=route_share` op publieke route → notificatie naar alle andere users (gebruik `Notification::fake()`).
- `createPost` met privé route van auteur → 422 via FormRequest (feature) / domain-exception (unit).
- `feed()` simplificatie: niet-eigenaar krijgt `route=null` voor privé route_share posts.

**Feature API:**
- `FeedIndexTest`: ongeauthenticeerd → 401; gepagineerd correct; per_page boundaries; sortering desc; eager-load count assertion (`DB::enableQueryLog`); privé-route filtering.
- `PostStoreTest`: 401; type=text happy path; type=route_share happy path (publieke route eigenaar én publieke route van ander); type=route_share met privé-route → 422; type=route_share met andermans-niet-publieke → 422; content > 2000 → 422; assert `Notification::fake` channels en recipients.
- `PostShowTest`: 401; eigen post 200; vreemde post 200 (publiek leesbaar — dit is een feed-app); privé-route binnen post → `route=null`.
- `PostDestroyTest`: 401; eigen 204; vreemde 403; cascade soft? Nee — hard delete (huidige migratie).
- `NotificationFlowTest`: 401; index lijst leeg → 200; na route-share dispatch → 1 notificatie zichtbaar bij andere user; markRead muteert `read_at`; readAll werkt batch-wijs; vreemde notificatie marken → 404/403.

## 7. Definition of Done

- [ ] `notifications`-tabel migratie aangemaakt en `migrate` groen.
- [ ] `Post` scope + `FeedService` + unit tests groen.
- [ ] `PostController` + `NotificationController` + FormRequest + Resources + Policy.
- [ ] `RouteSharedNotification` (queueable) verstuurt correct (Notification::fake assertions).
- [ ] Routes geregistreerd onder `auth:sanctum`.
- [ ] PHPUnit suite groen: `php artisan test --compact --filter='Feed|Post|Notification'`, coverage ≥80% over gewijzigde files.
- [ ] `php -l` schoon, `vendor/bin/pint` schoon.
- [ ] `docs/MotoTrax/` feed-flow + notification-flow gedocumenteerd.
- [ ] ADR onder `docs/MotoTrax/adr/` voor notification-fanout strategie (waarom "alle users" i.p.v. followers).

## 8. Build-volgorde (BUILD MODE — pas starten na APPROVED)

1. `php artisan notifications:table --no-interaction` → migratie + `migrate`.
2. Factory-states uitbreiden + `Post::scopeForFeed`.
3. `FeedService` + unit tests (`Notification::fake`).
4. `PostPolicy` + register.
5. `StorePostRequest` (incl. cross-field rule voor `route_share`/`maintenance`).
6. `PostResource` (API) + `NotificationResource`.
7. `RouteSharedNotification` (`ShouldQueue`, `database`).
8. `PostController` + `NotificationController` + routes/api.php.
9. Feature tests per groep, iteratief tot groen.
10. Pint + `php -l` + volledige `php artisan test --compact`.
11. Docs + ADR.
12. Gefaseerde commits per logische groep, expliciete goedkeuring per commit.

## 9. Approval log

- **2026-04-26** — Marcvdc gaf "PLAN MVP-005 APPROVED" met expliciete antwoorden op alle 6 open vragen (sectie 4). BUILD MODE mag starten.
