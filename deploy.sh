#!/bin/bash
set -e

PROJECT_PATH="/home/u234139505/domains/inventoryai.id/skm-ekp-app"
PUBLIC_PATH="/home/u234139505/domains/inventoryai.id/public_html/ekp"
REPO_TOKEN="${REPO_ACCESS_TOKEN}"

echo "========================================"
echo "🚀 Starting Deployment"
echo "========================================"

echo ""
echo "[1/8] Navigating to project..."
cd "$PROJECT_PATH"
pwd
echo "composer.json exists: $(test -f composer.json && echo YES || echo NO)"

echo ""
echo "[2/8] Adding GitHub to known hosts..."
ssh-keyscan -t ed25519,ecdsa,rsa github.com >> ~/.ssh/known_hosts 2>/dev/null || true

echo ""
echo "[3/8] Pulling latest code from Git..."
git remote set-url origin "https://${REPO_TOKEN}@github.com/Ghostwisnu/skm-ekp-app.git"
git fetch origin main
git reset --hard origin/main

echo ""
echo "[4/8] Installing Composer Dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
rm -f bootstrap/cache/config.php bootstrap/cache/services.php bootstrap/cache/packages.php
php artisan package:discover --ansi

echo ""
echo "[5/8] Building Frontend..."
npm ci
npm run build

echo ""
echo "[6/8] Clearing caches & Running migrations..."
php artisan config:clear
php artisan route:clear
php artisan migrate --force

echo ""
echo "[7/8] Seeding default data..."
php artisan db:seed --force || true

echo ""
echo "[8/8] Optimizing & syncing public folder..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
rsync -av --delete public/ "$PUBLIC_PATH/"
php artisan storage:link || true

echo ""
echo "========================================"
echo "✅ Deployment Complete!"
echo "========================================"