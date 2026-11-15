#!/usr/bin/env bash
set -euo pipefail

# scripts/docker-php.sh
# Draait een PHP-commando in de app-image van deze repo, als de host-gebruiker en zonder
# database of andere services. Bedoeld voor machines zonder lokale PHP, en voor de
# controles van de plugin hobby-ontwikkeling (.hobby-controles en .hobby-enkele-test).
#
# Gebruik: scripts/docker-php.sh <commando> [argumenten...]
#
# Voorbeelden:
#   scripts/docker-php.sh php artisan test --compact
#   scripts/docker-php.sh vendor/bin/pint --test
#
# Werkt ook vanuit een schone worktree. Ontbreekt .env, dan wordt die uit .env.example
# gemaakt en krijgt hij een APP_KEY. Ontbreekt vendor/, dan draait eerst composer install.
# Beide zijn gitignored, dus de werkmap blijft voor git schoon.
#
# Omgevingsvariabelen:
#   MOTOTRAX_PHP_IMAGE  image om in te draaien (standaard mvp-mototrax-app, de image van
#                       de compose-stack). Bestaat hij niet, dan wordt hij uit de Dockerfile
#                       gebouwd.
#
# De exitcode is die van het commando. Een mislukte voorbereiding stopt het script met een
# niet-nul exitcode.

if [[ $# -eq 0 ]]; then
  echo "Gebruik: $0 <commando> [argumenten...]" >&2
  exit 64
fi

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
IMAGE="${MOTOTRAX_PHP_IMAGE:-mvp-mototrax-app}"
COMPOSER_CACHE="${XDG_CACHE_HOME:-$HOME/.cache}/mototrax-composer"

cd "$REPO_ROOT"

if ! docker image inspect "$IMAGE" >/dev/null 2>&1; then
  echo "Image $IMAGE ontbreekt, bouwen uit de Dockerfile..." >&2
  docker build --quiet --tag "$IMAGE" "$REPO_ROOT" >&2
fi

mkdir -p "$COMPOSER_CACHE"

if [[ ! -f .env ]]; then
  echo "Geen .env, aanmaken uit .env.example" >&2
  cp .env.example .env
fi

docker_args=(
  run --rm
  --user "$(id -u):$(id -g)"
  --entrypoint ""
  --env HOME=/tmp
  --env COMPOSER_HOME=/tmp/composer
  --env COMPOSER_CACHE_DIR=/tmp/composer-cache
  --volume "$REPO_ROOT/php/local.ini:/usr/local/etc/php/conf.d/local.ini:ro"
  --volume "$COMPOSER_CACHE:/tmp/composer-cache"
  --workdir /var/www/html
  --volume "$REPO_ROOT:/var/www/html"
)

# Een gelinkte vendor/ zou de autoloader de code van de andere kant van de link laten laden.
# hobby-ontwikkeling 0.5.2 en hoger kopieert vendor/ bij het breken van tests; een link komt
# dan van een oudere versie, en die geeft hier liever een duidelijke fout dan een vals oordeel.
if [[ -L vendor ]]; then
  echo "vendor/ is een link; werk de plugin hobby-ontwikkeling bij naar 0.5.2 of hoger" >&2
  exit 1
fi

in_container() {
  docker "${docker_args[@]}" "$IMAGE" "$@"
}

if [[ ! -e vendor ]]; then
  echo "Geen vendor/, composer install draaien" >&2
  in_container composer install --no-interaction --no-progress --prefer-dist >&2
fi

if ! grep -q '^APP_KEY=.\+' .env; then
  echo "Lege APP_KEY in .env, sleutel genereren" >&2
  in_container php artisan key:generate --force >&2
fi

in_container "$@"
