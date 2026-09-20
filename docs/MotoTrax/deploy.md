# MotoTrax — Demo & lokale deploy-preview

Reproduceerbare lokale bring-up van MotoTrax met een volledige demo-dataset, op de
meegeleverde Docker-stack (Nginx + PHP-FPM 8.4 + PostgreSQL 15). Geen externe hosting nodig.

## Vereisten
- Docker + Docker Compose (v2)
- Een vrije poort **18081** (web) en **5433** (database) — zie [Poorten aanpassen](#poorten-aanpassen)

## Eerste keer opzetten

```bash
# 1. Env-bestand aanmaken (verder niets aanpassen)
cp .env.example .env

# 2. Stack bouwen en starten
docker compose up -d --build

# 3. Demo-data zaaien
docker compose exec app php artisan migrate:fresh --seed --force
```

Meer is het niet. Wat vroeger handwerk was, doet de stack nu zelf:

- **Database-instellingen** staan als `environment` op de app-service in
  `docker-compose.yml`, naast de postgres-service die ze beschrijft. Je hoeft geen
  DB-blok in `.env` te zetten; `environment` wint van `env_file`, dus de `sqlite`-regel
  uit `.env.example` breekt de stack niet.
- **De app-sleutel** wordt door de entrypoint gegenereerd als `APP_KEY` leeg is.
- **Frontend-assets** worden gebouwd door de `assets`-service, die eenmalig draait
  voordat app en nginx starten. Node op de host is niet meer nodig.
- **De container-user** valt terug op `1000:1000` als `UID`/`GID` niet gezet zijn, dus
  `export UID=$(id -u) GID=$(id -g)` is niet meer nodig. Wijkt jouw user daarvan af,
  zet ze dan alsnog.
- **Uploads** landen in `storage/app/private/` op de host in plaats van in een named
  volume, zodat ze automatisch van jouw user zijn. Laravel levert daar een `.gitignore`
  voor mee, dus ze komen niet in git terecht.

> **Upgrade je een bestaande checkout?** Het oude `storagedata`-volume staat niet meer in
> `docker-compose.yml`, dus `docker compose down -v` ruimt het niet op. Verwijder het
> eenmalig met `docker volume rm mvp-mototrax_storagedata`.

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
- **Permissie-fouten op `storage/`** of bestanden die van `root` blijken te zijn → je user
  wijkt af van `1000:1000`. Zet `export UID=$(id -u) GID=$(id -g)` vóór `docker compose up`,
  en ruim eerder aangemaakte root-bestanden op met
  `docker run --rm -v "$PWD":/w -w /w alpine rm -rf storage/framework/testing/disks`.
- **`MissingAppKey` / 500** → de entrypoint zet zelf een sleutel als `APP_KEY` leeg is.
  Blijft de fout staan, controleer dan of `.env` bestaat en schrijfbaar is voor je user.
- **Web reageert niet** → `docker compose ps` en `docker compose logs nginx app` controleren.
  Hangt de app-container op `Waiting for database...`, dan komt hij niet bij postgres; check
  `docker compose logs db`.
- **Routes tonen "Geen track beschikbaar" / geen kaart** → de storage was niet schrijfbaar bij
  het zaaien, waardoor `gpx_file` leeg bleef. Controleer de rechten op `storage/app/private/`
  en zaai opnieuw met `docker compose exec app php artisan migrate:fresh --seed --force`.
- **500 op `/login` of admin** → de Vite-manifest ontbreekt. Draai de assets-service opnieuw met
  `docker compose run --rm assets` en controleer dat `public/build/manifest.json` bestaat.
