#!/bin/bash
set -euo pipefail

REPOPATH="$(cd "$(dirname "$0")" && pwd)"
APPPATH="/home/slmalzxh/crmdemo_app"
PUBLICPATH="/home/slmalzxh/uat.loopsintegrated.co/crmdemo"

echo "[cpanel-deploy] Repo:   $REPOPATH"
echo "[cpanel-deploy] App:    $APPPATH"
echo "[cpanel-deploy] Public: $PUBLICPATH"

mkdir -p "$APPPATH"

if command -v rsync >/dev/null 2>&1; then
    rsync -a --delete \
        --exclude='.git' \
        --exclude='.env' \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='storage' \
        --exclude='public/uploads' \
        --exclude='uploads' \
        "$REPOPATH/" "$APPPATH/"

    if [ -d "$REPOPATH/public/uploads" ]; then
        mkdir -p "$APPPATH/public/uploads"
        rsync -a --ignore-existing "$REPOPATH/public/uploads/" "$APPPATH/public/uploads/"
    fi
    if [ -d "$REPOPATH/uploads" ]; then
        mkdir -p "$APPPATH/uploads"
        rsync -a --ignore-existing "$REPOPATH/uploads/" "$APPPATH/uploads/"
    fi
else
    tar --exclude='.git' \
        --exclude='.env' \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='storage' \
        --exclude='public/uploads' \
        --exclude='uploads' \
        -cf - -C "$REPOPATH" . | tar -xf - -C "$APPPATH"

    if [ -d "$REPOPATH/public/uploads" ]; then
        mkdir -p "$APPPATH/public/uploads"
        cp -rn "$REPOPATH/public/uploads/." "$APPPATH/public/uploads/" 2>/dev/null || true
    fi
    if [ -d "$REPOPATH/uploads" ]; then
        mkdir -p "$APPPATH/uploads"
        cp -rn "$REPOPATH/uploads/." "$APPPATH/uploads/" 2>/dev/null || true
    fi
fi

mkdir -p \
    "$APPPATH/storage/app/public" \
    "$APPPATH/storage/app/private" \
    "$APPPATH/storage/framework/cache/data" \
    "$APPPATH/storage/framework/sessions" \
    "$APPPATH/storage/framework/views" \
    "$APPPATH/storage/logs" \
    "$APPPATH/bootstrap/cache" \
    "$APPPATH/public/uploads/pos" \
    "$APPPATH/public/uploads/petty_cash_proofs" \
    "$APPPATH/public/uploads/petty_cash_signatures"

chmod -R 775 "$APPPATH/storage" "$APPPATH/bootstrap/cache" "$APPPATH/public/uploads"

if [ ! -f "$APPPATH/.env" ] && [ -f "$APPPATH/.env.example" ]; then
    cp "$APPPATH/.env.example" "$APPPATH/.env"
    sed -i.bak \
        -e 's|^APP_ENV=.*|APP_ENV=production|' \
        -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
        -e 's|^APP_URL=.*|APP_URL=https://uat.loopsintegrated.co/crmdemo|' \
        "$APPPATH/.env" && rm -f "$APPPATH/.env.bak"
    echo "[cpanel-deploy] Created $APPPATH/.env from .env.example — update DB_* and MAIL_* credentials."
fi

# Point /home/slmalzxh/uat.loopsintegrated.co/crmdemo -> /home/slmalzxh/crmdemo_app/public
mkdir -p "$(dirname "$PUBLICPATH")"
if [ -e "$PUBLICPATH" ] && [ ! -L "$PUBLICPATH" ]; then
    if [ -d "$PUBLICPATH" ] && [ -z "$(ls -A "$PUBLICPATH")" ]; then
        rmdir "$PUBLICPATH"
    else
        mv "$PUBLICPATH" "${PUBLICPATH}.bak.$(date +%Y%m%d%H%M%S)"
    fi
fi
ln -sfn "$APPPATH/public" "$PUBLICPATH"

# Pick PHP >= 8.2 binary on cPanel
PHP_BIN=""
for candidate in \
    /opt/cpanel/ea-php84/root/usr/bin/php \
    /opt/cpanel/ea-php83/root/usr/bin/php \
    /opt/cpanel/ea-php82/root/usr/bin/php \
    "$(command -v php || true)"; do
    if [ -n "$candidate" ] && [ -x "$candidate" ]; then
        PHP_BIN="$candidate"
        break
    fi
done

if [ -z "$PHP_BIN" ]; then
    echo "[cpanel-deploy] ERROR: No PHP binary found."
    exit 1
fi

export PATH="$(dirname "$PHP_BIN"):$PATH"
echo "[cpanel-deploy] Using PHP: $($PHP_BIN -v | head -n 1)"

COMPOSER_CMD=()
for candidate in \
    "$(command -v composer || true)" \
    /opt/cpanel/composer/bin/composer \
    /usr/local/bin/composer \
    "$HOME/bin/composer"; do
    if [ -n "$candidate" ] && [ -x "$candidate" ]; then
        COMPOSER_CMD=("$candidate")
        break
    fi
done

if [ ${#COMPOSER_CMD[@]} -eq 0 ] && [ -f "$HOME/composer.phar" ]; then
    COMPOSER_CMD=("$PHP_BIN" "$HOME/composer.phar")
fi

cd "$APPPATH"

if [ ${#COMPOSER_CMD[@]} -gt 0 ]; then
    echo "[cpanel-deploy] Running composer install..."
    "${COMPOSER_CMD[@]}" install --no-dev --optimize-autoloader --no-interaction
else
    echo "[cpanel-deploy] WARNING: Composer not found. Install vendor dependencies manually."
fi

if [ -f "$APPPATH/vendor/autoload.php" ] && [ -f "$APPPATH/.env" ]; then
    if grep -qE '^APP_KEY=$' "$APPPATH/.env"; then
        "$PHP_BIN" artisan key:generate --force
    fi
    "$PHP_BIN" artisan storage:link || true
    "$PHP_BIN" artisan migrate --force || echo "[cpanel-deploy] WARNING: migrate failed (verify DB settings in $APPPATH/.env)."
    "$PHP_BIN" artisan optimize:clear
    "$PHP_BIN" artisan config:cache
    "$PHP_BIN" artisan view:cache
fi

echo "[cpanel-deploy] Deployment complete."
