#!/usr/bin/env bash
# Laravel Forge deploy script for Frosty_System.
# In Forge: Site → Deployment → paste this file's contents, or run:
#   bash "$FORGE_SITE_PATH/forge-deploy.sh"
set -euo pipefail

cd "$FORGE_SITE_PATH"

git pull origin "$FORGE_SITE_BRANCH"

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

if [ -f artisan ]; then
    $FORGE_PHP artisan storage:link --force 2>/dev/null || true
    $FORGE_PHP artisan migrate --force
    $FORGE_PHP artisan config:cache
    $FORGE_PHP artisan route:cache
    $FORGE_PHP artisan view:cache
    $FORGE_PHP artisan event:cache
fi

if [ -f package.json ]; then
    npm install --no-audit --no-fund
    npm run build
fi

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'
    sudo -S service "$FORGE_PHP_FPM" reload
) 9>/tmp/fpmlock
