# ADR 0003 — Notification fanout naar alle users

- **Status:** Accepted
- **Datum:** 2026-05-10
- **Context:** MVP-005 (Feed & Social)

## Beslissing

Wanneer een rider een `route_share`-post aanmaakt, wordt een `RouteSharedNotification` verstuurd naar **alle andere geregistreerde users** via `Notification::send()`. Geen follower-filtering.

## Alternatieven overwogen

1. **Followers-tabel** (volger/gevolgde-relatie)
   - **Pro:** schaalt beter bij grote gebruikersaantallen; relevantere notificaties.
   - **Con:** vereist een extra tabel, seed-data, UI voor follow/unfollow en policy-aanpassingen — scope is te groot voor MVP.
2. **Broadcast (Pusher/Reverb)**
   - **Pro:** real-time delivery, no polling nodig.
   - **Con:** externe afhankelijkheid, aparte websocket-setup; out of scope voor MVP.
3. **Alle users (gekozen)**
   - **Pro:** eenvoudigste implementatie; werkt direct bij kleine community-omvang (MVP).
   - **Con:** schaalt slecht bij honderden users — fanout wordt dan een bottleneck in de queue.

## Gevolgen

- `FeedService::dispatchRouteShared` haalt alle users op (`User::query()->where('id', '!=', $actor->id)->get()`) en verstuurt via `Notification::send()`.
- `RouteSharedNotification implements ShouldQueue` → verstuurd via de queue (sync in tests, database/redis in productie), zodat de API-respons niet blokkeert.
- Database channel opslaat in `notifications` tabel (Laravel standaard schema met UUID primary key).
- **Migratie naar followers:** enkel `FeedService::dispatchRouteShared` hoeft aangepast te worden; geen impact op Notification-klasse, Resource of Controller.

## Mitigaties

- Voor MVP is de community klein; queue buffert de fanout asynchroon.
- Bij groei: vervang de `User::query()->where(...)` in `dispatchRouteShared` door een `$actor->followers` relatie zonder verdere wijzigingen in de rest van de stack.
