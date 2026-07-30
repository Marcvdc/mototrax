# Parallelle ontwikkeling via Git Worktrees

## Wanneer

Gebruik een aparte worktree zodra je aan iets wilt werken terwijl de hoofd-stack actief blijft. Bijvoorbeeld:

- Parallelle Jira-tickets / feature branches
- Migratie- of refactor-traject dat de hoofd-dev niet mag blokkeren
- Browsertest van een feature naast een werkende baseline

Niet gebruiken voor: hotfix op de huidige branch of een kleine refactor in de huidige stack — daar is een worktree overkill.

## Hoofdrepo-discipline

De hoofd-clone (`/home/brandnetel/projects/mototrax`, branch `master`) is **alleen voor master-sync**. Géén feature-werk, géén scratch-files, géén plan-bestanden in `.ai/plans/`. Reden:

- Untracked files in de hoofdrepo blokkeren `git pull --ff-only` zodra origin een file met dezelfde naam introduceert ("would be overwritten by merge"). Dit is precies hoe een gemergde feature-PR (die het bijbehorende plan tracked maakt) een schijnbaar onschuldige lokale plan-file in een blocker verandert.
- De hoofdrepo werkt als sync-knooppunt voor alle worktrees: vies werk kruipt door op elke nieuwe worktree die je vanaf `master` afsplitst.

## Vóór je `worktree-setup.sh` aanroept

1. **Lokale `master` fast-forwarden:** `git -C /home/brandnetel/projects/mototrax pull --ff-only`. Het script splitst de nieuwe branch af van **lokale** `master`; staat die achter, dan mist je nieuwe worktree net-gemergde features (incl. de migraties/services waar je plan op leunt).
2. **Hoofdrepo schoon:** `git status` in de hoofdrepo moet leeg zijn. Verplaats lopende plan-files of scratch-werk eerst naar de worktree waar ze bij horen.
3. **Bevestig de afhankelijkheden:** als je nieuwe feature op een nog niet-gemergde branch leunt, splits dan vanaf die branch (`git worktree add -b <naam> ../mototrax-<naam> <basis-branch>`) en niet via het script — het script forceert `master` als basis.

## Plan-files horen in de worktree

Schrijf `.ai/plans/<TICKET>.md` **in de worktree** (`../mototrax-<naam>/.ai/plans/<TICKET>.md`), niet in de hoofdrepo. Het plan reist dan mee op de feature-branch, wordt onderdeel van de PR-diff, en blokkeert geen pulls op master.

Als je tijdens het scopen al een DRAFT plan in de hoofdrepo hebt geschreven (bijv. omdat de worktree er nog niet was): verplaats hem naar de juiste worktree zodra die bestaat, vóór de eerste commit. Laat de hoofdrepo daarna leeg achter (`git status` clean).

## Hoe — altijd via het setup script

```bash
./scripts/worktree-setup.sh <naam> [offset]
```

Het script:
1. Maakt branch `<naam>` **vers afgesplitst van `master`** en de worktree op `../mototrax-<naam>`
2. Faalt als de branch al bestaat of het pad al in gebruik is (geen overschrijven)
3. Kopieert `.env` naar de nieuwe worktree en isoleert daarin:
   - `COMPOSE_PROJECT_NAME=mototrax_<naam>` (volume- en netwerk-isolatie)
   - `COMPOSE_FILE=docker-compose.yml:docker-compose.local.yml` (bypass van de gecommitte `docker-compose.override.yml`)
   - `APP_URL=http://localhost:<nieuwe-nginx-poort>`
4. Genereert `docker-compose.local.yml` in de worktree met:
   - Aparte `container_name` per service (`mototrax_<naam>_app/_db/_nginx`) — nodig omdat `docker-compose.yml` hardcoded namen heeft
   - Geïsoleerde poorten: nginx `18081 + offset`, db `5433 + offset`
5. Print URL's, branch-info en cleanup-commando

Vervolgens in de nieuwe worktree:

```bash
cd ../mototrax-<naam>
docker compose up -d
docker compose exec app php artisan migrate
```

Geen `-f` of `--env-file` nodig — `COMPOSE_FILE` in `.env` regelt dat.

## Verboden

- **Niet** handmatig een worktree aanmaken zonder het script — dan loopt de poort/container-isolatie uit de pas en botst de stack met de hoofd-dev.
- **Niet** branchen vanaf iets anders dan `master`. Het script forceert dit; omzeil het niet.
- **Niet** `docker-compose.local.yml` committen — staat in `.gitignore` en is per-worktree uniek.
- **Niet** `COMPOSE_FILE` of `COMPOSE_PROJECT_NAME` uit de gegenereerde `.env` halen — zonder deze wordt de gecommitte override met poorten 18081/5433 gebruikt en botst je stack met de hoofd-dev.
- **Niet** dezelfde branch in twee worktrees checkouten (Git verbiedt dit, maar zelf opletten).
- **Niet** plan-files of feature-werk in de hoofdrepo plaatsen — zie "Hoofdrepo-discipline" en "Plan-files horen in de worktree".
- **Niet** `worktree-setup.sh` aanroepen met een achterlopende lokale `master` — pull eerst, anders splits je van een verouderde basis en mis je gemergde dependencies.

## Cleanup

Veilige volgorde (zonder `--force`):

```bash
cd ../mototrax-<naam>
docker compose down -v                       # containers + volumes weg
rm docker-compose.local.yml .env              # script-output weg (anders meldt git "untracked")
cd -
git worktree remove ../mototrax-<naam>
git branch -d <naam>                          # alleen na merge in master; anders -D als je weet wat je doet
```

`--force` op `git worktree remove` of `git branch -D` alleen gebruiken als je hebt geverifieerd dat er geen niet-gepushte commits of niet-gegenereerde lokale wijzigingen verloren gaan.

## Cross-machine borging

Dit document staat in git, dus elke developer en elke Claude-sessie volgt dezelfde aanpak. MEMORY.md is **niet** geschikt — die is per-machine en zou niet meereizen.
