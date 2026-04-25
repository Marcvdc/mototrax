#!/usr/bin/env bash
set -euo pipefail

# scripts/worktree-setup.sh
# Maakt een Git worktree + geïsoleerde Docker stack voor parallelle ontwikkeling.
# Branch wordt altijd vers afgesplitst van master.
#
# Gebruik: ./scripts/worktree-setup.sh <naam> [offset]
#
# Voorbeeld:
#   ./scripts/worktree-setup.sh wip            # auto offset op basis van bestaande worktrees
#   ./scripts/worktree-setup.sh wip 2          # expliciete offset (poorten +2)

NAME="${1:-}"
EXPLICIT_OFFSET="${2:-}"

if [[ -z "$NAME" ]]; then
  echo "Gebruik: $0 <naam> [offset]" >&2
  exit 1
fi

if [[ ! "$NAME" =~ ^[a-z0-9][a-z0-9_-]*$ ]]; then
  echo "Naam mag alleen lowercase letters, cijfers, '-' en '_' bevatten." >&2
  exit 1
fi

REPO_ROOT="$(git rev-parse --show-toplevel)"
cd "$REPO_ROOT"

WORKTREE_PATH="$REPO_ROOT/../mototrax-${NAME}"
BRANCH="$NAME"
PROJECT_NAME="mototrax_${NAME}"
BASE_BRANCH="master"

if [[ -e "$WORKTREE_PATH" ]]; then
  echo "Pad bestaat al: $WORKTREE_PATH" >&2
  exit 1
fi

if git show-ref --verify --quiet "refs/heads/$BRANCH"; then
  echo "Branch '$BRANCH' bestaat al — kies een andere naam of verwijder de branch eerst." >&2
  exit 1
fi

if ! git show-ref --verify --quiet "refs/heads/$BASE_BRANCH"; then
  echo "Base-branch '$BASE_BRANCH' bestaat niet lokaal." >&2
  exit 1
fi

if [[ -n "$EXPLICIT_OFFSET" ]]; then
  OFFSET="$EXPLICIT_OFFSET"
else
  OFFSET="$(git worktree list | wc -l | tr -d ' ')"
fi

NGINX_PORT=$((18081 + OFFSET))
DB_FORWARD_PORT=$((5433 + OFFSET))

echo "==> Worktree aanmaken: $WORKTREE_PATH (branch: $BRANCH vanaf $BASE_BRANCH)"
git worktree add -b "$BRANCH" "$WORKTREE_PATH" "$BASE_BRANCH"

if [[ ! -f "$REPO_ROOT/.env" ]]; then
  echo "WAARSCHUWING: $REPO_ROOT/.env niet gevonden — sla .env-isolatie over." >&2
else
  TARGET_ENV="$WORKTREE_PATH/.env"
  echo "==> .env kopiëren en isoleren"
  cp "$REPO_ROOT/.env" "$TARGET_ENV"

  for key in COMPOSE_PROJECT_NAME COMPOSE_FILE APP_URL; do
    sed -i "/^${key}=/d" "$TARGET_ENV"
  done

  cat >> "$TARGET_ENV" <<EOF

# === Worktree isolatie (auto-gegenereerd door scripts/worktree-setup.sh) ===
COMPOSE_PROJECT_NAME=${PROJECT_NAME}
COMPOSE_FILE=docker-compose.yml:docker-compose.local.yml
APP_URL=http://localhost:${NGINX_PORT}
EOF
fi

LOCAL_COMPOSE="$WORKTREE_PATH/docker-compose.local.yml"
echo "==> docker-compose.local.yml genereren"
cat > "$LOCAL_COMPOSE" <<EOF
# Auto-gegenereerd door scripts/worktree-setup.sh — niet committen.
# Isoleert containers en poorten voor worktree '${NAME}'.
version: '3.8'

services:
  app:
    container_name: ${PROJECT_NAME}_app

  db:
    container_name: ${PROJECT_NAME}_db
    ports:
      - "${DB_FORWARD_PORT}:5432"

  nginx:
    container_name: ${PROJECT_NAME}_nginx
    ports:
      - "${NGINX_PORT}:80"
EOF

cat <<EOF

==> Worktree klaar: $WORKTREE_PATH

Volgende stappen:
  cd $WORKTREE_PATH
  docker compose up -d
  docker compose exec app php artisan migrate

Toegangs-URL's:
  Web:      http://localhost:${NGINX_PORT}
  Postgres: localhost:${DB_FORWARD_PORT}

Project name: ${PROJECT_NAME}
Branch:       ${BRANCH} (afgesplitst van ${BASE_BRANCH})

Cleanup later:
  cd $WORKTREE_PATH && docker compose down -v
  cd $REPO_ROOT && git worktree remove $WORKTREE_PATH && git branch -d ${BRANCH}
EOF
