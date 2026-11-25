#!/bin/bash
set -euo pipefail

APPPATH="$(cd "$(dirname "$0")" && pwd)"
echo "[cpanel-deploy] Deploying in-place at: $APPPATH"

cd "$APPPATH"

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

# Ensure LiteSpeed/Apache can traverse and read public files
chmod 755 "$APPPATH" "$APPPATH/artisan"
[ -f "$APPPATH/.htaccess" ] && chmod 644 "$APPPATH/.htaccess"
[ -f "$APPPATH/.env" ] && chmod 644 "$APPPATH/.env"

for dir in app bootstrap config database public resources routes storage tests uploads; do
    if [ -d "$APPPATH/$dir" ]; then
        find "$APPPATH/$dir" -type d -exec chmod 755 {} +
        find "$APPPATH/$dir" -type f -exec chmod 644 {} +
    fi
done

chmod -R 775 "$APPPATH/storage" "$APPPATH/bootstrap/cache" "$APPPATH/public/uploads"

if [ ! -f "$APPPATH/.env" ] && [ -f "$APPPATH/.env.example" ]; then
    cp "$APPPATH/.env.example" "$APPPATH/.env"
    sed -i.bak \
        -e 's|^APP_ENV=.*|APP_ENV=production|' \
        -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
        -e 's|^APP_URL=.*|APP_URL=https://uat.loopsintegrated.co/crmdemo|' \
        "$APPPATH/.env" && rm -f "$APPPATH/.env.bak"
    chmod 644 "$APPPATH/.env"
    echo "[cpanel-deploy] Created $APPPATH/.env from .env.example — update DB_* and MAIL_* credentials."
fi

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

if [ ${#COMPOSER_CMD[@]} -eq 0 ]; then
    for phar in "$APPPATH/composer.phar" "$HOME/composer.phar"; do
        if [ -f "$phar" ]; then
            COMPOSER_CMD=("$PHP_BIN" "$phar")
            break
        fi
    done
fi

if [ ${#COMPOSER_CMD[@]} -gt 0 ]; then
    echo "[cpanel-deploy] Running composer install..."
    "${COMPOSER_CMD[@]}" install --no-dev --optimize-autoloader --no-interaction
    [ -d "$APPPATH/vendor" ] && find "$APPPATH/vendor" -type d -exec chmod 755 {} + && find "$APPPATH/vendor" -type f -exec chmod 644 {} +
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
