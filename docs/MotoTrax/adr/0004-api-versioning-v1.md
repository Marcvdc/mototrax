# ADR 0004 — API-versioning via /api/v1 URL-prefix

- **Status:** Accepted
- **Datum:** 2026-07-30
- **Context:** MVP-006 (API Layer)

## Beslissing

Alle REST-endpoints krijgen een `/api/v1`-URL-prefix en route-namen onder `api.v1.*`. Toekomstige breaking changes landen in een nieuwe versie (`/api/v2`) naast v1.

## Overwogen alternatieven

1. **Unversioned `/api/*` houden** — minste churn, maar geen nette upgrade-weg; een breaking change zou bestaande clients direct breken.
2. **Header-based versioning** (`Accept: application/vnd.mototrax.v1+json`) — flexibeler, maar minder ontdekbaar en lastiger te testen/documenteren voor een klein team.
3. **URL-prefix `/api/v1` (gekozen)** — expliciet, ontdekbaar, triviaal te routeren en documenteren (Postman), en de conventie die de meeste consumers verwachten.

## Gevolgen

- Bestaande route-namen zijn hernoemd naar `api.v1.*`; de 5 interne `route()`-referenties en de feature-test-URL's zijn meebijgewerkt.
- Nieuwe majors komen als een aparte route-groep (`/api/v2`) zonder v1 te breken.
- De `throttle:api` (60/min) en `throttle:api-write` (20/min) rate limiters en de CORS-config (`config/cors.php`) hangen op de `api/*`-paden en gelden dus ook automatisch voor toekomstige versies.
