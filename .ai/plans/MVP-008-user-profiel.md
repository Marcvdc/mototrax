---
name: MVP-008 — User-profiel (locatie, motor-type, avatar)
description: User-profiel uitbreiden met locatie, motor-type en avatar over model, web-profiel, Filament en API, zonder het e-mail-lek terug te brengen
type: plan
---

# MVP-008 — User-profiel (locatie, motor-type, avatar)

- **Status:** APPROVED, Marcvdc, 2026-09-26 (alle voorstellen uit sectie 8)
- **Issue:** [Marcvdc/mototrax#13](https://github.com/Marcvdc/mototrax/issues/13), parent epic [#1](https://github.com/Marcvdc/mototrax/issues/1)
- **Type:** MEDIUM/COMPLEX (migratie, model, enum, service, web-profiel, Filament, API, tests)
- **Stack:** Laravel 13 / PHP 8.3 / Type C (Hybrid) / Filament v5 / Sanctum v4 / PHPUnit 12 / Postgres (tests: in-memory sqlite)
- **Voortbouwend op:** MVP-006 (`UserResource`, e-mail alleen voor jezelf), MVP-007 §8.4 (profiel afgesplitst)

## 1. Doel

Het laatste niet-afgevinkte MVP-onderdeel uit epic #1 afronden: een rijder kan in zijn profiel een **locatie**, een **motor-type** en een **avatar** vastleggen. Die velden zijn zichtbaar op het eigen profiel en komen via `UserResource` in de API.

## 2. Bevindingen, huidige staat

| Onderdeel | Staat | Gevolg voor dit plan |
|-----------|-------|----------------------|
| `users`-tabel | Alleen `name`, `email`, `password`, `is_admin` | Migratie met 3 nullable kolommen |
| `User`-model | `#[Fillable(['name','email','password'])]`, `casts()`-methode | Fillable en cast voor enum uitbreiden |
| Web-profiel | Breeze `ProfileController::update` + `ProfileUpdateRequest`, form zonder `enctype` | Form multipart maken, request uitbreiden, opslag naar service |
| Filament | Geen `UserResource`, panel zonder `->profile()`; `canAccessPanel` is `true` voor iedereen | Zie keuze 8.3 |
| API `GET /api/v1/users` | **Publiek** (geen `auth:sanctum`), gepagineerd, via `UserResource` | Nieuwe velden zijn anoniem zichtbaar; zie keuze 8.4 |
| API `GET /api/v1/user` | Geeft ruw `$request->user()` terug, geen Resource | Nieuwe velden en `is_admin` gaan ongefilterd mee; zie keuze 8.5 |
| Foto-patroon | `BikeService::persistImage` op `public`-disk, `Bike::getImageUrlAttribute` met placeholder-URL (via.placeholder.com, dienst bestaat niet meer) | Zelfde opslagpatroon, andere fallback (8.6) |
| `app/Enums/` | Bestaat nog niet | Eerste enum in de repo |

Preflight: issue #13 komt uit MVP-007 (2026-09); de genoemde lagen bestaan nog, behalve dat er geen Filament user-resource is (het issue noemt die als optie).

## 3. Acceptance Criteria

| ID | Criterium |
|----|-----------|
| AC-1 | Migratie voegt `location` (string 100, nullable), `motor_type` (string, nullable) en `avatar` (string pad, nullable) toe aan `users`; `down()` dropt ze. |
| AC-2 | Enum `App\Enums\MotorType` (string-backed, TitleCase-keys, Nederlandse labels via `getLabel()`); `User` cast `motor_type` naar deze enum. |
| AC-3 | Ingelogde gebruiker kan via `PATCH /profile` locatie, motor-type en avatar bijwerken. Validatie: `location` nullable max 100, `motor_type` `Rule::enum`, `avatar` nullable image jpeg/png/webp max 2048 KB. |
| AC-4 | Avatar wordt opgeslagen op de `public`-disk onder `avatars/`; bij vervangen of verwijderen wordt het oude bestand verwijderd. Gebruiker kan zijn avatar ook weghalen (`remove_avatar`). |
| AC-5 | `User::avatar_url` geeft de publieke URL of `null`; de UI toont bij `null` een fallback (8.6). Avatar zichtbaar op `profile.show` en in de navigatie. |
| AC-6 | `UserResource` bevat `location`, `motor_type` (value + label) en `avatar_url`; `email` blijft alleen voor de gebruiker zelf. |
| AC-7 | Filament: velden bewerkbaar volgens keuze 8.3. |
| AC-8 | Tests groen, ≥80% dekking op gewijzigde delen, Pint schoon; `DemoSeeder` vult locatie en motor-type voor de demo-users. |

## 4. Architectuur (Type C / Hybrid)

### Nieuwe bestanden
- `database/migrations/xxxx_xx_xx_xxxxxx_add_profile_fields_to_users_table.php`: AC-1.
- `app/Enums/MotorType.php`: cases, bv. `Naked`, `Sport`, `Toer`, `Adventure`, `Cruiser`, `Enduro`, `Klassiek`, `Scooter` (definitief in 8.1); implementeert Filament `HasLabel` zodat hij direct in een `Select` past.
- `app/Services/ProfileService.php`: `update(User $user, array $attributes): User`; zet avatar-upload om naar pad (patroon `BikeService`), ruimt het oude bestand op, handelt `remove_avatar` af en reset `email_verified_at` bij een gewijzigd e-mailadres (verhuist uit de controller).
- `tests/Unit/Enums/MotorTypeTest.php` (optioneel, alleen als labels logica krijgen).
- `tests/Feature/Services/ProfileServiceTest.php`
- `tests/Feature/ProfileAvatarTest.php` (of uitbreiding van `tests/Feature/ProfileTest.php`, zie sectie 6)

### Wijzigingen
- `app/Models/User.php`: fillable `location`, `motor_type`, `avatar`; cast `motor_type => MotorType::class`; accessor `getAvatarUrlAttribute(): ?string`.
- `database/factories/UserFactory.php`: `location` en `motor_type` in de definitie of in een `withProfile()`-state (default leeg houden zodat bestaande tests niet veranderen).
- `app/Http/Requests/ProfileUpdateRequest.php`: regels uit AC-3 plus `remove_avatar` boolean.
- `app/Http/Controllers/ProfileController.php`: `update()` delegeert naar `ProfileService` (Breeze-controller is bestaande code; alleen de gewijzigde methode gaat via de service).
- `resources/views/profile/partials/update-profile-information-form.blade.php`: `enctype="multipart/form-data"`, velden locatie, motor-type (`<select>` uit de enum), avatar-upload met preview van de huidige en een verwijder-vinkje.
- `resources/views/profile/show.blade.php` + `resources/views/layouts/navigation.blade.php`: avatar/fallback, locatie en motor-type tonen. Nieuwe teksten in het Nederlands.
- `app/Http/Resources/UserResource.php`: AC-6.
- `routes/api.php`: alleen bij keuze 8.4b of 8.5a.
- `app/Providers/Filament/AdminPanelProvider.php` + eventueel `app/Filament/Pages/Auth/EditProfile.php`: alleen bij keuze 8.3a.
- `database/seeders/DemoSeeder.php`: locatie en motor-type per demo-user.
- `tests/Feature/Api/Users/UserIndexTest.php`: structuur uitbreiden met de nieuwe velden.
- `tests/Feature/Seeders/DemoSeederTest.php`: assert dat demo-users een motor-type hebben.
- `docs/MotoTrax/architecture.md` + `docs/MotoTrax/api/` (Postman): nieuwe velden in `UserResource`.

## 5. Niet-doelen
- Geen publieke profielpagina van andere rijders (`/riders/{user}`); alleen het eigen profiel en de API.
- Geen avatar in de geneste `user`-objecten van `BikeResource`, `RouteResource` en `PostResource` (kan als vervolg, zie 8.7).
- Geen beeldbewerking (croppen, resizen, queue-job). Als uploads groot blijken, is een `ShouldQueue`-job een vervolgticket.
- Geen wijziging aan registratie, tenzij 8.2 anders kiest.
- Geen herziening van `RoutePolicy::viewAny` of panel-toegang.

## 6. Test-strategie (PHPUnit, `Storage::fake('public')`)
1. `ProfileTest`: `PATCH /profile` met `location` + `motor_type` slaat beide op en redirect naar `profile.edit`; ongeldige `motor_type` geeft een validatiefout op dat veld.
2. `ProfileAvatarTest`: upload van een `UploadedFile::fake()->image('a.jpg')` staat daarna in `avatars/` op de fake disk en `avatar_url` verwijst ernaar; een tweede upload verwijdert het eerste bestand; `remove_avatar=1` verwijdert bestand en kolom; een pdf of een bestand >2 MB wordt geweigerd.
3. Bestaande Breeze-test "e-mail gewijzigd reset verificatie" blijft groen (regressie op de verhuisde logica).
4. `ProfileServiceTest`: service zonder HTTP-laag, zelfde scenario's als 2 voor randgevallen (geen avatar meegegeven laat bestaande avatar staan).
5. `UserIndexTest`: `data.*` bevat `location`, `motor_type`, `avatar_url`; `email` ontbreekt voor anonieme bezoekers en voor andere users (bestaande test blijft staan).
6. Filament (bij 8.3a): Livewire-test op de profielpagina, velden opslaan werkt en de avatar belandt op de public disk.

Draaien: `php artisan test --compact --filter='Profile|UserIndex|DemoSeeder'` in de docker-testomgeving (zie #11 voor het commando).

## 7. Definition of Done
- [ ] AC-1 t/m AC-8 groen; suite groen; Pint schoon.
- [ ] `docs/MotoTrax/architecture.md` en Postman-collectie bijgewerkt.
- [ ] Epic #1: profiel-regel afgevinkt.
- [ ] Geen ADR nodig, tenzij 8.4 kiest voor het publiek maken of juist afschermen van `/users` (dan ADR 0006).

## 8. Beslissingen (APPROVED, Marcvdc 2026-09-26: alle voorstellen overgenomen)

| # | Vraag | Eigenaar | Status | Voorstel |
|---|-------|----------|--------|----------|
| 8.1 | Motor-type: vaste enum of vrij tekstveld? En welke waarden? | Marcvdc | besloten | **Enum** met de 8 waarden uit sectie 4. Filterbaar, consistent, direct bruikbaar als `Select` in Filament. |
| 8.2 | Nieuwe velden ook bij registratie, of alleen bij profiel bewerken? | Marcvdc | besloten | **Alleen profiel bewerken.** Registratie blijft kort; het issue noemt registratie, maar alle velden zijn optioneel. |
| 8.3 | Filament: (a) ingebouwde profielpagina via `->profile()` met een eigen `EditProfile` die de extra velden toont, (b) nieuwe admin-`UserResource`, (c) niets in Filament? | Marcvdc | besloten | **(a)**: elke gebruiker komt in het panel (`canAccessPanel` is `true`), dus daar hoort de eigen profielpagina. (b) is gebruikersbeheer en hoort bij het panel-toegang-ticket. |
| 8.4 | `GET /api/v1/users` is publiek. Mogen locatie, motor-type en avatar anoniem zichtbaar zijn? (a) ja, (b) `/users` achter `auth:sanctum`, (c) `location` alleen voor jezelf, net als `email` | Marcvdc | besloten | **(c)**: locatie is persoonsgegeven; motor-type en avatar publiek. Houdt de endpoint publiek zoals afgesproken in MVP-006. |
| 8.5 | `GET /api/v1/user` geeft het ruwe model terug, dus straks ook `avatar` (pad) en al `is_admin`. Omzetten naar `UserResource`? | Marcvdc | besloten | **Ja**, kleine wijziging, sluit aan op de CLAUDE.md-regel "Eloquent Resources voor alle API responses". |
| 8.6 | Fallback zonder avatar: initialen in de UI (lokaal), of een externe dienst (ui-avatars.com)? | Marcvdc | besloten | **Initialen in Blade/Filament**, `avatar_url` in de API is dan `null`. Geen naam naar een derde partij. Voorlopig in `User::getAvatarUrlAttribute` + een Blade-component `x-user-avatar`. |
| 8.7 | Avatar ook in de geneste `user`-objecten van feed/routes/bikes in de API? | Marcvdc | besloten | **Nee, vervolgticket.** Houdt deze PR klein. |
