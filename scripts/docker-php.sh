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
)

# De sabotagecheck van de onafhankelijke review linkt vendor/ uit een andere worktree.
# Composer rekent de paden naar app/ uit vanaf de echte plek van vendor/, dus via die link
# zou de autoloader de code uit de andere worktree laden. Een mount op vendor/ volgt de link
# ook. Daarom komt in dat geval elk item uit de repo-root los in /work te staan, en het doel
# van de link als gewone map op /work/vendor.
if [[ -L vendor ]]; then
  vendor_doel="$(readlink -f vendor)"
  if [[ ! -d "$vendor_doel" ]]; then
    echo "vendor/ is een link naar $vendor_doel, maar die map bestaat niet" >&2
    exit 1
  fi
  docker_args+=(
    --tmpfs "/work:uid=$(id -u),gid=$(id -g)"
    --workdir /work
    --volume "$vendor_doel:/work/vendor:ro"
  )
  for item in .[!.]* *; do
    [[ -e "$item" && "$item" != vendor ]] || continue
    docker_args+=(--volume "$REPO_ROOT/$item:/work/$item")
  done
else
  docker_args+=(--workdir /var/www/html --volume "$REPO_ROOT:/var/www/html")
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
