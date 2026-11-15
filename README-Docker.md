# Docker Setup for MotoTrax

## Quick Start

### Option 1: Default Ports (8080, 5433)
```bash
docker-compose -f docker-compose.yml -f docker-compose.override.yml up -d --build
```

### Option 2: Custom Ports (Recommended for multiple apps)
1. Copy the example configuration:
   ```bash
   cp docker-compose.local.yml.example docker-compose.local.yml
   ```

2. Edit `docker-compose.local.yml` to change ports (e.g., 8081:80, 5434:5432)

3. Start with custom ports:
   ```bash
   docker-compose -f docker-compose.yml -f docker-compose.local.yml up -d --build
   ```

## Port Management

If you have multiple Docker apps running:

- **Web ports**: Change nginx port mapping (8080:80 → 8081:80, 8082:80, etc.)
- **Database ports**: Change db port mapping (5433:5432 → 5434:5432, 5435:5432, etc.)

## Database Access

- **Host**: localhost
- **Port**: 5433 (or your custom port)
- **Database**: mototrax
- **Username**: mototrax_user
- **Password**: mototrax_password

## Application Access

- **URL**: http://localhost:8080 (or your custom port)
- **Admin Panel**: http://localhost:8080/admin

## Development Workflow

1. Make code changes locally
2. Containers auto-reload with volume mounts
3. Access logs: `docker-compose logs -f app`
4. Stop containers: `docker-compose down`

## Tests en Pint zonder lokale PHP

`scripts/docker-php.sh` draait een PHP-commando in de app-image, als je eigen gebruiker en zonder
database (de tests gebruiken sqlite in het geheugen). Ontbreken `.env` of `vendor/`, dan maakt
het script die eerst aan.

```bash
scripts/docker-php.sh php artisan test --compact
scripts/docker-php.sh vendor/bin/pint --test
```

`.hobby-controles` en `.hobby-enkele-test` gebruiken hetzelfde script, zodat de controles van de
plugin hobby-ontwikkeling (en de onafhankelijke review) ook zonder PHP op de host draaien.

## Troubleshooting

- **Port conflicts**: Edit docker-compose.local.yml for different ports
- **Permission issues**: Ensure storage directory is writable: `chmod -R 777 storage bootstrap/cache`
- **Database connection**: Verify PostgreSQL is running and ports match
