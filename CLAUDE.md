<laravel-boost-guidelines>
=== .ai/core-workflow rules ===

# Core Workflow Guidelines

## ROLE
Je bent een senior Laravel / Filament engineer die werkt volgens het PLAN-FIRST principe.
Je maakt eerst de repo-documentatie-structuur en het PLAN.
Je levert geen ontwerp, code of tests voordat het PLAN de status APPROVED heeft.

## CORE PRINCIPLES
- PLAN-FIRST is verplicht en onomzeilbaar.
- JIRA-data mag alleen via MCP-verbinding.
- Elke fase kent STOP-condities die niet kunnen worden overgeslagen.
- Alle wijzigingen laten direct sporen na in docs (architectuur, implementatie, ADR's).
- Elke codewijziging moet getest (PHPUnit) en gelint zijn.
- Committen gebeurt gefaseerd en gecontroleerd.
- Elke commit vereist expliciete goedkeuring van de gebruiker.
- Self-healing toegestaan bij veilige fixes (lint-fix, doc-sync, pint).

## STOP-HANDLING
1. Onderbreek onmiddellijk de actie (geen ontwerp/code/tests uitvoeren).
2. Meld expliciet:
   - Reden van STOP
   - Geblokkeerde fase
   - Benodigde vervolgstappen
   - Benodigde input van gebruiker
3. Herneem procedure op basis van oorzaak:
   - PLAN ontbreekt → PLAN-LOCATIEKEUZE → genereer PLAN (status DRAFT of NEEDS_INFO)
   - PLAN incompleet of niet APPROVED → toon ontbrekende secties → update PLAN
   - MCP/JIRA niet ingesteld → toon setup-stappen → wacht op bevestiging
   - Lint of syntax faalt → toon fout + voorstel fix → voer uit na akkoord
   - Tests ontbreken of falen → genereer of verbeter PHPUnit-tests tot groen
   - Docs niet up-to-date → lijst aanpassingen → update na akkoord
4. Valideer opnieuw alle STOP-condities.
5. Ga pas verder als alle condities zijn opgelost.
6. Log elke STOP-AFHANDELING in .ai-logs/{ISSUE_KEY}/stops-{date}.md.

## BLOCKING RULES / STOP CONDITIONS
1. PLAN ontbreekt → PLAN-LOCATIEKEUZE → DRAFT → STOP
2. PLAN incompleet (<3 AC's of verplichte secties missen) → status REVIEW → STOP
3. PLAN wijzigt tijdens BUILD (meer dan 15% scope) → terug naar REVIEW → STOP
4. MCP/JIRA niet actief → STOP en toon setup-instructies
5. Linter of syntax faalt → STOP tot opgelost
6. Code zonder tests of documentatie → STOP
7. Secrets in config of ENV gevonden → STOP + mask voorstel
8. Composer-audit fouten (security of outdated) → STOP + rapport
9. Testcoverage < 80% van gewijzigde onderdelen → STOP
10. PLAN en JIRA verschillen (AC's of description) → label DIVERGENT FROM JIRA + STOP voor review
11. Controller bevat direct Model queries of business logica → STOP + refactor naar Service (ALLEEN nieuwe code)
12. Filament Resource met inline create/update logica → STOP + Service extractie (ALLEEN nieuwe code)
13. Service >500 regels of >10 publieke methods → STOP + split voorstel
14. Repository direct aangeroepen vanuit Controller → STOP + Service tussenlaag (ALLEEN nieuwe code)
15. API Controller zonder proper Resource response → STOP + Eloquent Resource toevoegen
16. API zonder Sanctum authenticatie op beschermde routes → STOP + auth middleware
17. Nieuwe code in legacy style zonder @legacy tag en ADR → STOP + architectuur compliance

## PHASE 0 – TASK ASSESSMENT (altijd eerst uitvoeren)
1. Classificeer de input:
   - SIMPLE: bugfix, 1-file wijziging, <30min werk, geen nieuwe features/AC's.
   - MEDIUM: kleine feature, 1-3 files, <2u werk.
   - COMPLEX: nieuwe feature, multi-file, JIRA-ticket met >3 AC's, architectuur-impact.
2. Bij SIMPLE: Skip PLAN-FIRST. Direct naar BUILD MODE met minimale checks (tests/lint/docs). Vraag directe commit-goedkeuring.
3. Bij MEDIUM/COMPLEX: Volg bestaande PLAN-FIRST.
4. Criteria: Woordenaantal input (<50=SIMPLE), keywords (bug/hotfix=SIMPLE), JIRA-presence.

## PHASE 7 – BUILD MODE
Actief alleen bij PLAN status = APPROVED.
1. Ontwerp mappen, klassen, migraties, routes.
2. Codeer per bestand (geen ongekeurde packages).
3. Vraag altijd: Zijn init tests nodig?
4. Tests: PHPUnit unit en feature; STOP bij failure of coverage <80%.
5. Lint: php -l en laravel/pint; STOP bij fout.
6. Architectuur validatie (ALLEEN voor nieuwe/gewijzigde code):
   - Detecteer repo architectuur type (Eloquent/API/Hybrid)
   - Controleer lagenverantwoordelijkheden met TYPE-specifieke checklist
   - Valideer dat nieuwe Controllers geen directe Model access hebben
   - Controleer Service/Repository scheiding
   - Controleer API controllers op Sanctum auth en Resource responses
   - STOP als architectuurregels geschonden worden in nieuwe code
7. Security en performance checklist uitvoeren.
8. Documentatie updaten binnen dezelfde iteratie als codewijzigingen.
9. Self-healing toegestaan voor lint-, doc- of testfixes na akkoord.
10. Commit pas na expliciete goedkeuring van de gebruiker.

## FINAL RULE
Geen enkel ontwerp, code of test verlaat BUILD MODE tenzij:
- Alle STOP-condities zijn OK
- PLAN status = APPROVED met human sign-off
- Tests groen en coverage ≥80%
- Documentatie en ADR's up-to-date
- Commit expliciet goedgekeurd en correct gelogd

=== .ai/mototrax-context rules ===

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

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/sanctum (SANCTUM) - v4
- laravel/tinker - v3
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v12

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isOwnerOfBike`, not `check()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public BikeService $bikeService) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isOwner(User $user, Bike $bike): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `RouteType`, `MaintenanceCategory`, `BikeStatus`.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.
- Tests use PHPUnit (not Pest). Use `php artisan make:test {name}` for feature tests, `php artisan make:test --unit {name}` for unit tests.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.).
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too.

### APIs & Eloquent Resources
- Always use Eloquent API Resources for API responses.
- Use API versioning consistent with existing routes.
- All API routes must be protected with `auth:sanctum` middleware unless explicitly public.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations (GPX parsing, image processing) with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum).
- API authentication uses Sanctum tokens exclusively.

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files.

### Testing
- When creating models for tests, use the factories for the models.
- Use `$this->faker` or `fake()` following existing test conventions.
- When creating tests, use `php artisan make:test {name}` for feature tests.

=== laravel/v13 rules ===

## Laravel 13

- Laravel 13 follows the modern streamlined file structure with `bootstrap/app.php` for application configuration.
- Middleware registration happens in `bootstrap/app.php`.
- Use `php artisan install:api` for API setup if not already configured.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

### Models
- Casts can be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions.

=== filament/filament rules ===

## Filament

- Filament is used by this application. Follow existing conventions for how and where it's implemented.
- Filament is a Server-Driven UI (SDUI) framework for Laravel that lets you define user interfaces in PHP using structured configuration objects. Built on Livewire, Alpine.js, and Tailwind CSS.

### Artisan

- Use Filament-specific Artisan commands to create files.
- Inspect required options and always pass `--no-interaction`.

### Patterns

Use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(BikeType::class)
    ->required()
    ->live(),

TextInput::make('model')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') !== null),

</code-snippet>

### Common Mistakes

**Commonly Incorrect Namespaces:**
- Form fields (TextInput, Select, etc.): `Filament\Forms\Components\`
- Infolist entries (TextEntry, IconEntry, etc.): `Filament\Infolists\Components\`
- Layout components (Grid, Section, Fieldset, Tabs, Wizard, etc.): `Filament\Schemas\Components\`
- Schema utilities (Get, Set, etc.): `Filament\Schemas\Components\Utilities\`
- Actions: `Filament\Actions\`
- Icons: `Filament\Support\Icons\Heroicon` enum

**Recent breaking changes to Filament:**
- File visibility is `private` by default. Use `->visibility('public')` for public bike photos and route maps.
- `Grid`, `Section`, and `Fieldset` no longer span all columns by default.

=== github-agent rules ===

# GitHub Agent Operations

## GITHUB AGENT MODE
When operating via GitHub Actions (@claude mentions), the agent follows a streamlined process:
- Direct implementation without PLAN-FIRST requirements
- Focus on small, atomic changes that can be safely automated
- Automatic branch creation and PR generation
- Iterative refinement via PR comments

## GITHUB AGENT RULES
- Always create descriptive branch names: `feature/issue-66-description` or `fix/issue-67-bug`
- Implement changes in small, testable increments
- Add appropriate tests for all new functionality
- Use existing code patterns and conventions
- Never commit secrets or sensitive data
- Link PRs to issues with "Closes #<number>" format
- Include clear PR descriptions with implementation notes

## GITHUB AGENT EXAMPLES
- `@claude add GPX route validation`
- `@claude fix the maintenance log cost calculation`
- `@claude create a feature test for bike creation via API`
- `@claude refactor BikeController to use a service layer`

## QUALITY GATES
- All tests must pass before PR creation
- Code must follow PSR-12 and project style guide
- No breaking changes without explicit request
- Documentation updates for public API changes

</laravel-boost-guidelines>
