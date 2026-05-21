#!/usr/bin/env bash
set -euo pipefail

SITE=/home/forge/frosty.diybizrewards.com

cd "${SITE}"

git fetch origin main
git reset --hard origin/main

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

if [ -f package.json ]; then
    npm install --no-audit --no-fund
    npm run build
fi

php artisan migrate --force
php artisan storage:link --force 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
chmod -R ug+rwx storage bootstrap/cache

echo DEPLOY_OK
