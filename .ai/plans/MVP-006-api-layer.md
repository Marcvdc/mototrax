---
name: MVP-006 — API Layer
description: Hardening van de REST API tot een consistente, veilige, gedocumenteerde laag (rate limiting, CORS, Postman) + resterende endpoints conform conventies
type: plan
---

# MVP-006 — API Layer

- **Status:** APPROVED — Marcvdc, 2026-07-30 (keuzes sectie 8)
- **Parent issue:** [Marcvdc/mototrax#1](https://github.com/Marcvdc/mototrax/issues/1) → sub `#MVP-006 API Layer`
- **Type:** COMPLEX (multi-file: middleware/config/controllers/resources/requests/policies/tests/docs)
- **Voortbouwend op:** MVP-004 (routes), MVP-005 (feed) — kern-endpoints bestaan al.

## 1. Doel

De REST API afronden tot een productiewaardige laag: **rate limiting**, **CORS**, en een **Postman-collectie**, plus de resterende endpoints (`bikes`, `users`) op het niveau brengen van de rest van de codebase (Eloquent Resources, Form Requests, Policies), inclusief het dichten van een **privacy-lek**.

## 2. Bevindingen — huidige staat

| Onderdeel | Status | Probleem |
|-----------|--------|----------|
| Rate limiting | ❌ | Geen throttle op de `api`-middlewaregroep; geen `RateLimiter::for('api')`. |
| CORS | ❌ | Geen `config/cors.php`; draait op framework-defaults (`allowed_origins: *`). |
| `GET /api/users` | 🔴 **security** | Publiek (geen auth) én lekt `email` van álle users; geen paginatie, geen Resource. |
| `BikeController` | ⚠️ | Geen Resource (inline `->map()`), geen Form Request (inline `validate()`), ownership inline i.p.v. Policy, geeft raw model terug bij store/update. |
| Postman-collectie | ❌ | Bestaat niet. |
| `RouteController`/`PostController` | ✅ | Al conform (Resource + FormRequest + Policy). |

## 3. Acceptance Criteria (concept — afhankelijk van scope, sectie 8)

| ID | Criterium |
|----|-----------|
| AC-1 | Alle `api`-routes vallen onder een benoemde rate limiter (`RateLimiter::for('api')`); default **60 req/min** per user (auth) of per IP (gast). Overschrijding → `429` met `Retry-After`. |
| AC-2 | Strengere limiter op schrijf/upload-endpoints (POST/PUT routes/bikes/posts): default **20 req/min**. |
| AC-3 | `config/cors.php` aanwezig; `allowed_origins` env-gedreven (`CORS_ALLOWED_ORIGINS`), default afhankelijk van sectie 8. `paths: ['api/*']`, `supports_credentials` correct t.o.v. Sanctum. |
| AC-4 | 🔴 `GET /api/users` lekt geen `email` meer; wordt (afh. sectie 8) auth-only en/of via `UserResource` met alleen publieke velden; gepagineerd. |
| AC-5 | `BikeController` gerefactord: `BikeResource`, `StoreBikeRequest`/`UpdateBikeRequest`, `BikePolicy` (ownership), gepagineerde index; geen inline validatie/ownership meer. |
| AC-6 | Consistente JSON-foutafhandeling voor de API (`withExceptions`): 401/403/404/422/429 als nette JSON i.p.v. HTML. |
| AC-7 | Postman-collectie (`docs/MotoTrax/api/mototrax.postman_collection.json`) met alle endpoints, Sanctum-bearer-variabele en voorbeeldbodies. |
| AC-8 | Feature-tests: rate-limit (429 na N hits), CORS-preflight headers, `GET /users` lekt geen email, Bike CRUD via Resource/Policy (ownership → 403). |

## 4. Architectuur (Type C / Hybrid)

### Nieuwe bestanden
- `config/cors.php`
- `app/Http/Resources/UserResource.php`, `app/Http/Resources/BikeResource.php`
- `app/Http/Requests/Api/StoreBikeRequest.php`, `UpdateBikeRequest.php`
- `app/Policies/BikePolicy.php`
- `app/Services/BikeService.php` (indien scope B — image-upload/persist orchestratie)
- Postman-collectie onder `docs/MotoTrax/api/`
- Tests: `tests/Feature/Api/RateLimitTest.php`, `CorsTest.php`, `Bike/*`, `Users/UserIndexTest.php`

### Wijzigingen
- `bootstrap/app.php` — `RateLimiter::for('api', ...)` + `withRouting(using/api throttle)`; `withExceptions` JSON-rendering voor `api/*`.
- `routes/api.php` — throttle-middleware op de groep + strenge limiter op writes; `GET /users` auth + paginatie.
- `app/Http/Controllers/Api/BikeController.php` + `UserController.php` — dun, delegeren naar Resource/Request/Policy(/Service).
- `.env.example` — `CORS_ALLOWED_ORIGINS`.

## 5. Niet-doelen
- Geen nieuwe domein-features (geen likes/comments/followers — dat is post-MVP).
- Geen wijziging aan het Filament-admin.
- Geen OpenAPI/Swagger-generator (Postman-collectie volstaat voor MVP).

## 6. Test-strategie (≥80% op gewijzigde delen)
- Rate limit: N+1 hits → 429 + `Retry-After`.
- CORS: OPTIONS-preflight → juiste `Access-Control-Allow-*` headers.
- Privacy: `GET /users` bevat geen `email`; auth-gedrag conform sectie 8.
- Bike CRUD: store/update/destroy via Policy (vreemde bike → 403), response via Resource.

## 7. Definition of Done
- [ ] AC-1 t/m AC-8 groen; suite groen (`php artisan test`); Pint schoon op gewijzigde bestanden.
- [ ] `docs/MotoTrax/architecture.md` API-sectie bijgewerkt (rate limit + CORS + endpoints).
- [ ] ADR indien een niet-triviale keuze (bv. versioning) wordt gemaakt.
- [ ] Postman-collectie werkt tegen de lokale stack.

## 8. Beslissingen (APPROVED — Marcvdc 2026-07-30)
1. **Scope:** volledig incl. security-fix — rate limiting + CORS + Postman **én** Bike/User-hardening (Resource/FormRequest/Policy) + email-lek dichten.
2. **CORS-origins:** env-gedreven via `CORS_ALLOWED_ORIGINS`, default `*`.
3. **Versioning:** `/api/v1`-prefix introduceren; route-namen naar `api.v1.*` (5 app-referenties + 50 test-URL's mee bijwerken).
