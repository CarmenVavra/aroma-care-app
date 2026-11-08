#!/usr/bin/env bash
# ============================================================================
#  AROMACARE - Deployment auf netcup (Apache + PHP, Subdomain aromacare.caryssa.at)
#
#  Ablauf: Git-Stand pruefen -> Tests, Pint, Larastan -> Release lokal bauen
#  (git archive + composer --no-dev + npm run build) -> per scp hochladen ->
#  per ssh aktivieren (Migrationen, Caches, atomarer Symlink-Wechsel).
#  Auf dem Server werden weder git noch composer noch node benoetigt.
#
#  Aufruf:
#    ./deploy.sh --setup        einmalig: Ordner und .env auf dem Server anlegen
#    ./deploy.sh                Release bauen, hochladen und aktivieren
#    ./deploy.sh --skip-tests   nur im Notfall: ohne Tests, Pint und Larastan
#    ./deploy.sh --build-only   Release lokal bauen, nichts hochladen (zum Pruefen)
#    ./deploy.sh --allow-dirty  trotz nicht committeter Aenderungen (deployt wird HEAD)
#
#  Konfiguration: .deploy.env neben diesem Skript (Vorlage: .deploy.env.example).
#  Einrichtung und Fehlersuche: DEPLOYMENT.md
#
#  Layout auf dem Server (relativ zum SSH-Home = Webspace-Root):
#    aromacare.caryssa.at/app  -> aromacare-app/releases/<release>   (Symlink)
#                                         Dokumentstamm: aromacare.caryssa.at/app/public
#    aromacare.caryssa.at/aromacare-app/releases/<release>/
#    aromacare.caryssa.at/aromacare-app/shared/.env, shared/storage/, shared/database/
#    aromacare.caryssa.at/aromacare-app/uploads/                  (temporaer)
# ============================================================================
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

MODE=deploy
SKIP_TESTS=0
ALLOW_DIRTY=0
for arg in "$@"; do
    case "$arg" in
        --setup) MODE=setup ;;
        --build-only) MODE=build ;;
        --skip-tests) SKIP_TESTS=1 ;;
        --allow-dirty) ALLOW_DIRTY=1 ;;
        -h | --help) sed -n '2,19p' "${BASH_SOURCE[0]}"; exit 0 ;;
        *) echo "Unbekannte Option: $arg (siehe ./deploy.sh --help)" >&2; exit 2 ;;
    esac
done

if [[ -f .deploy.env ]]; then
    # shellcheck disable=SC1091
    source .deploy.env
fi

DEPLOY_PORT="${DEPLOY_PORT:-22}"
DEPLOY_SITE="${DEPLOY_SITE:-aromacare.caryssa.at}"
DEPLOY_APP_DIR="${DEPLOY_APP_DIR:-aromacare-app}"
DEPLOY_LINK="${DEPLOY_LINK:-app}"
DEPLOY_PHP="${DEPLOY_PHP:-}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
DEPLOY_KEEP="${DEPLOY_KEEP:-5}"
DEPLOY_URL="${DEPLOY_URL:-https://aromacare.caryssa.at}"

if [[ $MODE != build ]]; then
    : "${DEPLOY_USER:?DEPLOY_USER fehlt - .deploy.env aus .deploy.env.example anlegen}"
    : "${DEPLOY_HOST:?DEPLOY_HOST fehlt - .deploy.env aus .deploy.env.example anlegen}"
fi

REMOTE="${DEPLOY_USER:-}@${DEPLOY_HOST:-}"
BASE="$DEPLOY_SITE/$DEPLOY_APP_DIR"
LINK="$DEPLOY_SITE/$DEPLOY_LINK"

step() { printf '\n\033[1;33m==> %s\033[0m\n' "$*"; }
fail() { printf '\n\033[1;31m[FEHLER] %s\033[0m\n' "$*" >&2; exit 1; }

ssh_run() { ssh -p "$DEPLOY_PORT" "$REMOTE" "$@"; }
# Zieldateinamen immer explizit angeben (MSYS-scp unter Git Bash uebernimmt sonst den ganzen Pfad).
scp_put() { scp -q -P "$DEPLOY_PORT" "$1" "$REMOTE:$2"; }

for tool in ssh scp tar; do
    command -v "$tool" >/dev/null 2>&1 || fail "$tool wurde nicht gefunden."
done

# ----------------------------------------------------------------------------
#  Skripte, die auf dem Server laufen (werden per scp hochgeladen)
# ----------------------------------------------------------------------------
remote_common() {
    cat <<'COMMON'
#!/usr/bin/env bash
set -euo pipefail

log() { printf '  -> %s\n' "$*"; }

find_php() {
    local c
    for c in "${PHP_BIN:-}" /usr/local/php84/bin/php /usr/local/php83/bin/php /usr/local/php82/bin/php \
        /opt/plesk/php/8.4/bin/php /opt/plesk/php/8.3/bin/php /opt/plesk/php/8.2/bin/php \
        php8.4 php8.3 php8.2 php; do
        [ -z "$c" ] && continue
        if command -v "$c" >/dev/null 2>&1 && "$c" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' 2>/dev/null; then
            echo "$c"
            return 0
        fi
    done
    return 1
}

PHP="$(find_php)" || { echo "[FEHLER] Kein PHP >= 8.2 gefunden. DEPLOY_PHP in .deploy.env setzen." >&2; exit 1; }
log "PHP: $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"
COMMON
}

remote_setup() {
    cat <<'SETUP'
BASE_REL="$1"
mkdir -p "$HOME/$BASE_REL"
BASE="$(cd "$HOME/$BASE_REL" && pwd)"

for ext in ctype dom fileinfo filter hash mbstring openssl pcre PDO pdo_sqlite session tokenizer xml curl; do
    "$PHP" -m | grep -qix "$ext" || echo "  !  PHP-Erweiterung fehlt: $ext (in Plesk unter PHP-Einstellungen aktivieren)"
done

log "Lege Verzeichnisse in $BASE an"
mkdir -p "$BASE/releases" "$BASE/uploads" \
    "$BASE/shared/storage/app/public" \
    "$BASE/shared/storage/framework/cache/data" \
    "$BASE/shared/storage/framework/sessions" \
    "$BASE/shared/storage/framework/views" \
    "$BASE/shared/storage/logs"     "$BASE/shared/database"
chmod -R u+rwX "$BASE/shared/storage" "$BASE/shared/database"
[ -f "$BASE/shared/database/database.sqlite" ] || { touch "$BASE/shared/database/database.sqlite"; log "SQLite-Datenbank angelegt."; }

if [ ! -f "$BASE/shared/.env" ]; then
    KEY="base64:$("$PHP" -r 'echo base64_encode(random_bytes(32));')"
    cp "$BASE/uploads/env.production" "$BASE/shared/.env"
    sed -i "s|^APP_KEY=.*|APP_KEY=$KEY|" "$BASE/shared/.env"
    chmod 600 "$BASE/shared/.env"
    log "$BASE/shared/.env angelegt (APP_KEY generiert)."
else
    log "$BASE/shared/.env existiert bereits - unveraendert."
fi
rm -f "$BASE/uploads/env.production" "$BASE/uploads/remote.sh"
SETUP
}

remote_activate() {
    cat <<'ACTIVATE'
BASE_REL="$1"
LINK_REL="$2"
RELEASE="$3"
KEEP_RELEASES="${KEEP_RELEASES:-5}"

BASE="$(cd "$HOME/$BASE_REL" && pwd)"
LINK_DIR="$(cd "$HOME/$(dirname "$LINK_REL")" && pwd)"
LINK_PATH="$LINK_DIR/$(basename "$LINK_REL")"
RELEASE_DIR="$BASE/releases/$RELEASE"
ARCHIVE="$BASE/uploads/aromacare-$RELEASE.tgz"

case "$BASE" in
    "$LINK_DIR"/*) REL_TARGET="${BASE#"$LINK_DIR"/}/releases/$RELEASE" ;;
    *) echo "[FEHLER] $BASE muss unterhalb von $LINK_DIR liegen." >&2; exit 1 ;;
esac

[ -f "$BASE/shared/.env" ] || { echo "[FEHLER] $BASE/shared/.env fehlt. Zuerst ./deploy.sh --setup ausfuehren." >&2; exit 1; }
[ -f "$ARCHIVE" ] || { echo "[FEHLER] Archiv $ARCHIVE nicht gefunden." >&2; exit 1; }

# Impressum/Datenschutz duerfen nicht mit Platzhaltern online gehen.
if grep -qE "^LEGAL_(NAME|EMAIL)=\"?(Vorname Nachname|kontakt@example\.com)?\"?\$" "$BASE/shared/.env"; then
    echo "[FEHLER] LEGAL_NAME/LEGAL_EMAIL in $BASE/shared/.env sind leer oder Platzhalter." >&2
    exit 1
fi

log "Entpacke Release $RELEASE"
mkdir -p "$RELEASE_DIR"
tar -xzf "$ARCHIVE" -C "$RELEASE_DIR"
rm -f "$ARCHIVE"

# Relative Links: Die SSH-Shell bei netcup ist chrooted und sieht andere absolute Pfade als der Webserver.
rm -rf "$RELEASE_DIR/storage"
ln -s "../../shared/storage" "$RELEASE_DIR/storage"
ln -sfn "../../shared/.env" "$RELEASE_DIR/.env"
# SQLite-Datenbank liegt in shared/ und ueberlebt jedes Release.
ln -sfn "../../../shared/database/database.sqlite" "$RELEASE_DIR/database/database.sqlite"
mkdir -p "$RELEASE_DIR/bootstrap/cache"

cd "$RELEASE_DIR"

log "Package Discovery"
"$PHP" artisan package:discover --ansi

if [ -L "$LINK_PATH" ]; then
    log "Wartungsmodus an"
    (cd "$LINK_PATH" && "$PHP" artisan down --retry=15) || true
fi
# Bei einem Fehler (z. B. Migration) die alte Version wieder freigeben; der Link bleibt unveraendert.
trap 'echo "[FEHLER] Deployment abgebrochen - altes Release bleibt aktiv." >&2; (cd "$RELEASE_DIR" && "$PHP" artisan up >/dev/null 2>&1) || true' ERR

log "Migrationen"
"$PHP" artisan migrate --force

log "Startrezepte abgleichen"
"$PHP" artisan db:seed --class=RecipeSeeder --force

# Kein config:cache/view:cache/route:cache: Die wuerden die Chroot-Pfade der SSH-Shell fest einbacken
# (z. B. in der Closure der Health-Route /up) und beim Webserver mit open_basedir-Fehlern enden.
log "Caches"
"$PHP" artisan optimize:clear
"$PHP" artisan event:cache

log "Aktiviere Release"
# Plesk legt den Dokumentstamm beim Speichern der Hosting-Einstellungen als echten Ordner an.
# Den zur Seite schieben (nicht loeschen), damit der Link ein Symlink werden kann.
if [ -d "$LINK_PATH" ] && [ ! -L "$LINK_PATH" ]; then
    mv "$LINK_PATH" "$LINK_PATH.plesk-$(date +%Y%m%d-%H%M%S)"
fi
ln -sfn "$REL_TARGET" "$LINK_PATH.tmp"
mv -Tf "$LINK_PATH.tmp" "$LINK_PATH"

"$PHP" artisan up

log "Behalte die letzten $KEEP_RELEASES Releases"
cd "$BASE/releases"
ls -1t | tail -n +"$((KEEP_RELEASES + 1))" | xargs -r rm -rf
rm -f "$BASE/uploads/remote.sh"

log "Release $RELEASE ist aktiv."
ACTIVATE
}

# Laedt Gemeinsames + Skript hoch und fuehrt es mit Argumenten aus.
run_remote() {
    local script="$1"
    shift
    local file="$WORK_TMP/remote.sh"
    { remote_common; "$script"; } >"$file"
    ssh_run "mkdir -p '$BASE/uploads'"
    scp_put "$file" "$BASE/uploads/remote.sh"
    ssh_run "PHP_BIN='$DEPLOY_PHP' KEEP_RELEASES='$DEPLOY_KEEP' bash '$BASE/uploads/remote.sh' $*"
}

WORK_TMP="$(mktemp -d)"
trap 'rm -rf "$WORK_TMP"' EXIT

probe_ssh() {
    if ! ssh -p "$DEPLOY_PORT" -o BatchMode=yes -o ConnectTimeout=10 "$REMOTE" true 2>/dev/null; then
        printf '\033[1;33mHinweis: SSH-Anmeldung ohne Passwort nicht moeglich - ohne Schluessel wird mehrfach nach dem Passwort gefragt (siehe DEPLOYMENT.md).\033[0m\n'
    fi
}

# Vorlage .env.production.example, ergaenzt um MAIL_* und LEGAL_* aus der lokalen .env.
build_env_production() {
    local line key value
    while IFS= read -r line || [[ -n $line ]]; do
        key="${line%%=*}"
        if [[ $line == *=* && $key =~ ^(MAIL_(HOST|PORT|USERNAME|PASSWORD|FROM_ADDRESS)|LEGAL_[A-Z_]+)$ && -f .env ]]; then
            value="$(grep -E "^${key}=" .env | head -n 1 | cut -d= -f2- || true)"
            if [[ -n $value && $value != '""' && $value != *'['* ]]; then
                printf '%s=%s
' "$key" "$value"
                continue
            fi
        fi
        printf '%s
' "$line"
    done <.env.production.example
}

# ----------------------------------------------------------------------------
#  Einmalige Einrichtung
# ----------------------------------------------------------------------------
if [[ $MODE == setup ]]; then
    [[ -f .env.production.example ]] || fail ".env.production.example fehlt."
    step "Richte $REMOTE ein (Ordner: ~/$BASE)"
    probe_ssh
    ssh_run "mkdir -p '$BASE/uploads'"
    build_env_production >"$WORK_TMP/env.production"
    scp_put "$WORK_TMP/env.production" "$BASE/uploads/env.production"
    ssh_run "sed -i 's/\r\$//' '$BASE/uploads/env.production'"
    run_remote remote_setup "'$BASE'"
    cat <<HINT

Server ist vorbereitet. Naechste Schritte (Details: DEPLOYMENT.md):
  1. ~/$BASE/shared/.env pruefen (MAIL_* und LEGAL_* wurden aus der lokalen .env uebernommen)
  2. In Plesk fuer $DEPLOY_SITE: PHP 8.3, open_basedir, Dokumentstamm $DEPLOY_SITE/$DEPLOY_LINK/public, SSL
  3. Danach: ./deploy.sh
HINT
    exit 0
fi

# ----------------------------------------------------------------------------
#  1. Git-Stand und Qualitaetspruefung
# ----------------------------------------------------------------------------
step "1/5  Git-Stand pruefen"
branch="$(git rev-parse --abbrev-ref HEAD)"
[[ $branch == "$DEPLOY_BRANCH" ]] || fail "Aktueller Branch ist '$branch', deployt wird '$DEPLOY_BRANCH'."
if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    if [[ $ALLOW_DIRTY -eq 1 ]]; then
        printf '\033[1;33mHinweis: nicht committete Aenderungen werden NICHT mit deployt (nur HEAD).\033[0m\n'
    else
        fail "Es gibt nicht committete Aenderungen. Erst committen oder mit --allow-dirty (deployt trotzdem nur HEAD)."
    fi
fi
commit="$(git rev-parse --short HEAD)"
if [[ -n "$(git rev-list '@{u}..HEAD' 2>/dev/null || true)" ]]; then
    printf '\033[1;33mHinweis: HEAD ist noch nicht nach GitHub gepusht.\033[0m\n'
fi

if [[ $SKIP_TESTS -eq 0 ]]; then
    step "2/5  Qualitaetspruefung (Tests, Stil, statische Analyse)"
    php artisan test --compact
    ./vendor/bin/pint --test
    ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress
else
    step "2/5  Qualitaetspruefung uebersprungen (--skip-tests)"
fi

# ----------------------------------------------------------------------------
#  2. Release bauen
# ----------------------------------------------------------------------------
release="$(date +%Y%m%d-%H%M%S)-$commit"
work="$WORK_TMP/release"
archive="$WORK_TMP/aromacare-$release.tgz"
mkdir -p "$work"

step "3/5  Release $release bauen"
git archive --format=tar HEAD | tar -x -C "$work"

[[ -d node_modules ]] || npm ci --no-audit --no-fund
npm run build
cp -r public/build "$work/public/build"

(
    cd "$work"
    composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress
)

for p in tests tools UI_Mockups .claude CLAUDE.md start.bat stop.bat deploy.sh DEPLOYMENT.md phpstan.neon phpunit.xml \
    .deploy.env.example .env.production.example node_modules .env public/hot database/database.sqlite; do
    rm -rf "${work:?}/$p"
done
printf '%s' "$commit" >"$work/REVISION"

tar -czf "$archive" -C "$work" .
echo "Release-Archiv: $(du -h "$archive" | cut -f1)"

if [[ $MODE == build ]]; then
    kept="${TMPDIR:-/tmp}/aromacare-$release.tgz"
    cp "$archive" "$kept"
    echo "Build-only: nichts hochgeladen. Archiv: $kept"
    exit 0
fi

# ----------------------------------------------------------------------------
#  3. Hochladen und aktivieren
# ----------------------------------------------------------------------------
step "4/5  Auf $DEPLOY_HOST hochladen"
probe_ssh
ssh_run "mkdir -p '$BASE/uploads'"
scp_put "$archive" "$BASE/uploads/aromacare-$release.tgz"

step "5/5  Release auf dem Server aktivieren"
run_remote remote_activate "'$BASE'" "'$LINK'" "'$release'"

step "Fertig"
status="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$DEPLOY_URL/up" 2>/dev/null || true)"
if [[ $status == 200 ]]; then
    printf '\033[1;32mRelease %s (%s) ist online: %s\033[0m\n' "$release" "$commit" "$DEPLOY_URL"
else
    printf '\033[1;33mRelease %s ist aktiv, %s/up antwortet aber mit "%s". Siehe DEPLOYMENT.md (Fehlersuche).\033[0m\n' "$release" "$DEPLOY_URL" "${status:-keine Antwort}"
fi

