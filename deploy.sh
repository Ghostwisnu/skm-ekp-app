#!/bin/bash
# Script Auto-Deploy Laravel ke Hostinger
set -e

echo "🚀 [1/6] Turning on Maintenance Mode..."
php artisan down || true

echo "📥 [2/6] Pulling latest code from Git..."
git fetch origin main
git reset --hard origin/main

echo "📦 [3/6] Installing Composer Dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "🎨 [4/6] Installing NPM Packages & Building Frontend..."
npm ci
npm run build

echo "🗄️ [5/6] Running Database Migrations..."
php artisan migrate --force

echo "🌱 [5b/6] Seeding Default Data..."
php artisan db:seed --force || true

echo "⚡ [6/6] Clearing & Caching Optimizations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan storage:link || true

echo "🟢 Turning off Maintenance Mode..."
php artisan up

echo "✅ Deployment Successful!"
