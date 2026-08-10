# MotoTrax — Demo & lokale deploy-preview

Reproduceerbare lokale bring-up van MotoTrax met een volledige demo-dataset, op de
meegeleverde Docker-stack (Nginx + PHP-FPM 8.4 + PostgreSQL 15). Geen externe hosting nodig.

## Vereisten
- Docker + Docker Compose (v2)
- Een vrije poort **18081** (web) en **5433** (database) — zie [Poorten aanpassen](#poorten-aanpassen)

## Eerste keer opzetten

```bash
# 1. Env-bestand aanmaken
cp .env.example .env

# 2. Database op de meegeleverde PostgreSQL-container richten
#    (vervang het DB-blok in .env door onderstaande waarden)
cat >> .env <<'ENV'
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=mototrax
DB_USERNAME=mototrax_user
DB_PASSWORD=mototrax_password
ENV

# 3. De container draait als je eigen user (voorkomt permissie-problemen op volumes)
export UID=$(id -u) GID=$(id -g)

# 4. Stack bouwen en starten
docker compose up -d --build

# 5. Storage schrijfbaar maken voor de app-user
#    De named volume op storage/app/private is initieel root-owned; zonder deze stap
#    kan de app geen GPX-bestanden wegschrijven en laden route-kaarten niet.
docker compose run --rm --user root --no-deps --entrypoint sh app \
  -c "chown -R $(id -u):$(id -g) storage/app/private bootstrap/cache"

# 6. App-sleutel genereren
docker compose exec app php artisan key:generate

# 7. Frontend-assets bouwen (op de host; vereist Node 18+)
#    De web-app (login/dashboard/route-kaart) heeft de Vite-manifest nodig.
npm install
npm run build

# 8. Database migreren + demo-data zaaien
docker compose exec app php artisan migrate:fresh --seed --force
```

> **Let op:** heb je in stap 1 al een `.env` met een `DB_CONNECTION`-regel, verwijder dan
> eerst het bestaande DB-blok zodat de waarden uit stap 2 niet dubbel staan.

## Openen
| Onderdeel | URL |
|-----------|-----|
| Web-app | http://localhost:18081 |
| Admin (Filament) | http://localhost:18081/admin |
| API | http://localhost:18081/api/v1 |

### Demo-credentials
| Rol | E-mail | Wachtwoord |
|-----|--------|------------|
| Admin | `admin@mototrax.dev` | `password` |
| Rider | `jan@mototrax.dev` | `password` |

Alle demo-riders (`jan`, `sanne`, `youssef`, `emma` `@mototrax.dev`) hebben wachtwoord `password`.

## Wat de demo-data bevat
De `DemoSeeder` levert een deterministische set:
- **5 gebruikers** (1 admin + 4 riders)
- **9 motoren** met in totaal **27 onderhoudslogs**
- **10 routes** met een écht GPX-bestand op de disk → kaartpreview én download werken
- **20 feed-berichten** (route-shares, onderhoudsupdates en tekstberichten)

## Demo-data resetten
```bash
docker compose exec app php artisan migrate:fresh --seed --force
```

## Stack stoppen
```bash
docker compose down            # containers weg, data blijft in volumes
docker compose down -v         # ook de database- en storage-volumes wissen
```

## Poorten aanpassen
De standaard poorten staan in `docker-compose.override.yml` (nginx `18081`, db `5433`).
Bij een conflict:

```bash
cp docker-compose.local.yml.example docker-compose.local.yml
# pas de poorten aan in docker-compose.local.yml, en start met:
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d --build
```

## Problemen oplossen
- **Permissie-fouten op `storage/`** → controleer dat je `export UID=$(id -u) GID=$(id -g)`
  hebt gedraaid vóór `docker compose up`.
- **`MissingAppKey` / 500** → `docker compose exec app php artisan key:generate` en herlaad.
- **Web reageert niet** → `docker compose ps` en `docker compose logs nginx app` controleren.
- **Routes tonen "Geen track beschikbaar" / geen kaart** → de storage was niet schrijfbaar bij het zaaien.
  Draai stap 5 (chown) en zaai opnieuw met stap 7.
- **500 op `/login` of admin** → frontend-assets niet gebouwd. Draai lokaal `npm install && npm run build`
  (de `public/build/`-map met de Vite-manifest is vereist).
