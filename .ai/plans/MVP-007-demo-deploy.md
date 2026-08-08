---
name: MVP-007 — Demo & Deploy
description: Coherente demo-dataset (seeders), reproduceerbare bring-up/deploy-preview en README + screenshots — de laatste epic-sub voor een presenteerbare MVP v1.0
type: plan
---

# MVP-007 — Demo & Deploy

- **Status:** APPROVED — Marcvdc, 2026-08-08 (beslissingen sectie 8; sign-off voor BUILD)
- **Parent issue:** [Marcvdc/mototrax#1](https://github.com/Marcvdc/mototrax/issues/1) → sub `#MVP-007 Demo & Deploy`
- **Type:** COMPLEX (multi-file: seeders/factories/docs/assets; mogelijk deploy-config)
- **Voortbouwend op:** MVP-004 (routes/GPX), MVP-005 (feed), MVP-006 (API) — alle domeinen bestaan; dit maakt ze *presenteerbaar*.

## 1. Doel
De MVP v1.0 demonstreerbaar en overdraagbaar maken: één **coherente demo-dataset** die met één commando reproduceerbaar is, een **reproduceerbare bring-up/deploy-preview**, en een **README + screenshots** die een nieuwe gebruiker in minuten op weg helpt.

## 2. Bevindingen — huidige staat
| Onderdeel | Status | Probleem |
|-----------|--------|----------|
| Seeders | ⚠️ wildgroei | 6 seeders (`AdminUserSeeder`, `SimpleDemoSeeder`, `MotoTraxSeeder`, `RouteDemoSeeder`, `SimpleBikeSeeder`, `DatabaseSeeder`) met overlappende scope; `DatabaseSeeder` roept alleen `AdminUserSeeder` + `SimpleDemoSeeder` (3 users) aan. Geen enkele, gedocumenteerde demo-set. |
| Demo-routes | 🔴 | Gezaaide routes krijgen een **fake `gpx_file`-pad**; er staat geen echt GPX-bestand op de public disk → kaartpreview/download werkt niet in een demo. Enige echte GPX's zitten in `tests/Fixtures/gpx/`. |
| Demo-media | ❌ | Geen demo-foto's voor bikes/avatars; `image`-velden leeg of fake. |
| Deploy-preview | ❌ | Alleen lokale dev-Docker (`README.md` + `README-Docker.md`); geen gedocumenteerde/geautomatiseerde preview-bring-up, geen prod-profiel. |
| README | ⚠️ | Aanwezig maar deels achterhaald: noemt `/api/*` (nu `/api/v1/*`), PostgreSQL/PHP-versies, geen screenshots, geen "één-commando demo". |
| User-profiel | 🔴 open scope | Epic-tabel: *Gebruikers — profiel (locatie, motor-type, avatar)* is het **enige niet-afgevinkte** MVP-onderdeel. Zie beslissing 8.4. |

## 3. Acceptance Criteria (concept — afhankelijk van sectie 8)
| ID | Criterium |
|----|-----------|
| AC-1 | Eén canonieke `DemoSeeder` (naam t.b.d.) produceert een **deterministische** demo-set: **5 users** (incl. de admin), elk met 1–3 bikes, per bike onderhoudslogs, **10 routes** totaal, en een gevulde feed (route_share + maintenance posts). Idempotent draaibaar (`migrate:fresh --seed`). |
| AC-2 | Gezaaide routes verwijzen naar **echte GPX-bestanden** op de public disk (afgeleid van `tests/Fixtures/gpx/sample-track.gpx`), zodat kaartpreview én download in de demo werken. |
| AC-3 | Redundante seeders opgeruimd/geconsolideerd (afh. 8.2); `DatabaseSeeder` roept de canonieke set aan; `php artisan migrate:fresh --seed` slaagt schoon. |
| AC-4 | Demo-media: bikes (en avatars, afh. 8.4) krijgen een gezaaide placeholder-afbeelding op de public disk, of een gedocumenteerde afwezigheid met nette fallback. |
| AC-5 | **Deploy-preview = reproduceerbare lokale Docker-demo** (8.1a): één gedocumenteerd commando brengt de stack + demo-data op (`docs/MotoTrax/deploy.md`), inclusief storage-symlink en `migrate:fresh --seed`. Geen hosting. |
| AC-6 | README herschreven: één-commando demo, correcte `/api/v1`-endpoints, juiste stack-versies, demo-credentials, en **echte screenshots** (via draaiende app, 8.3a) in `docs/screenshots/`. |
| AC-7 | Tests: een seeder-smoke-test (`migrate:fresh --seed` levert de verwachte aantallen; gezaaide GPX-bestanden bestaan op disk). Bestaande suite blijft groen; Pint schoon op gewijzigde bestanden. |

## 4. Architectuur (Type C / Hybrid)
### Nieuwe/gewijzigde bestanden (indicatief)
- `database/seeders/DemoSeeder.php` (nieuw, canoniek) + herziene `DatabaseSeeder.php`.
- **Verwijderen** (afh. 8.2): `MotoTraxSeeder`, `SimpleDemoSeeder`, `RouteDemoSeeder`, `SimpleBikeSeeder` (behoud `AdminUserSeeder`).
- `database/seeders/data/` of `database/factories/` aanpassing voor GPX-seed (kopie van fixture naar `storage/app/public/routes/…`).
- `database/seeders/data/` demo-assets (placeholder-afbeeldingen) — of gebruik van een gegenereerde placeholder.
- `tests/Feature/Seeders/DemoSeederTest.php` (nieuw, smoke).
- `README.md` (herschrijven), `docs/screenshots/` (nieuw, gegenereerde beelden).
- `docs/MotoTrax/deploy.md` (nieuw): één-commando lokale demo-bring-up (8.1a) — géén prod-compose/hosting.
- User-profiel valt **buiten** MVP-007 (8.4) → apart ticket.

## 5. Niet-doelen
- Geen nieuwe domein-features buiten wat de demo presenteerbaar maakt (geen likes/comments/followers).
- Geen CI/CD-pipeline naar een cloud-provider tenzij 8.1 dat expliciet kiest.
- Geen echte prod-secrets in de repo (placeholders + `.env.example`).

## 6. Test-strategie (≥80% op gewijzigde delen)
- Seeder-smoke: na `migrate:fresh --seed` → exact verwachte record-aantallen (users/bikes/routes/posts) en **bestaan van gezaaide GPX-bestanden** op de public disk.
- Regressie: volledige suite blijft groen (seeders raken factories/relaties).

## 7. Definition of Done
- [ ] AC-1 t/m AC-7 groen; suite groen (`php artisan test`); Pint schoon.
- [ ] `README.md` bijgewerkt + screenshots; `docs/MotoTrax/architecture.md` waar relevant.
- [ ] Deploy-preview reproduceerbaar volgens 8.1; gedocumenteerd.
- [ ] ADR indien een niet-triviale deploy-/scope-keuze wordt gemaakt (bv. hosting-target of profiel-uitbreiding).

## 8. Beslissingen (VASTGELEGD — Marcvdc, 2026-08-08)
1. **Deploy-preview = (a)** reproduceerbare lokale Docker-demo + `docs/MotoTrax/deploy.md`. Géén hosting, geen prod-compose.
2. **Seeder-consolidatie = ja** (aanbevolen default): één canonieke `DemoSeeder`, `AdminUserSeeder` behouden, de 4 redundante seeders (`MotoTraxSeeder`, `SimpleDemoSeeder`, `RouteDemoSeeder`, `SimpleBikeSeeder`) verwijderen.
3. **Screenshots = (a)** ik genereer ze via de draaiende app (browser-automatisering) → `docs/screenshots/`.
4. **User-profiel = buiten scope** → apart ticket (locatie/motor-type/avatar; migratie + Filament + API + tests).
