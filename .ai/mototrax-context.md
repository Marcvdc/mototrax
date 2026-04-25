# MotoTrax Project Context

## Domain
MotoTrax is een motorrijders community platform voor het bijhouden van motoren, onderhoud, GPX-routes en sociale interactie.
Gebruikers kunnen hun motoren beheren, onderhoudslogboeken bijhouden, routes uploaden/delen en berichten plaatsen in de community feed.

## Key Models
- **Bike**: Motorfietsen met foto's en specificaties per gebruiker
- **MaintenanceLog**: Onderhoudslogboeken met kosten en werkzaamheden per motor
- **Route**: GPX-routes die geüpload en gedeeld worden door rijders
- **Post**: Social feed berichten en ervaringen van riders
- **User**: Motorrijders met profiel en API-tokens (Sanctum)

## Important Conventions
- Gebruik Nederlands voor user-facing tekst en comments
- Volg bestaande Filament resource patterns
- API endpoints gebruiken Sanctum authenticatie
- Eloquent API Resources voor alle API responses
- Docker-gebaseerde ontwikkelomgeving

## Architecture Notes
- Type C (Hybrid) architectuur — Filament admin + REST API via Sanctum
- Filament v5 voor admin interface
- Laravel Sanctum voor API authenticatie
- PHPUnit voor testing (geen Pest)
- API Controllers in `app/Http/Controllers/Api/`
- Filament Resources in `app/Filament/Resources/`

## Business Rules
- Elke Bike is gekoppeld aan één User
- MaintenanceLogs zijn altijd gekoppeld aan een specifieke Bike
- Routes kunnen publiek of privé zijn
- API toegang vereist geldig Sanctum token
- Foto uploads voor motoren en routes

## File Structure Patterns
- Models: `app/Models/`
- Filament Resources: `app/Filament/Resources/`
- API Controllers: `app/Http/Controllers/Api/`
- Web Controllers: `app/Http/Controllers/`
- Services: `app/Services/` (aanmaken bij noodzaak)
- Tests: `tests/Feature/` en `tests/Unit/`
- Documentation: `docs/MotoTrax/`
