#!/bin/bash

# Laravel Cloud Deployment Script
# ================================

echo "🚀 Starting Laravel Cloud deployment..."

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies and build assets
echo "📦 Installing Node dependencies..."
npm ci

echo "🔨 Building frontend assets..."
npm run build

# Run Laravel optimizations
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Run tenant migrations
echo "🏢 Running tenant migrations..."
php artisan tenants:migrate --force

# Clear and warm caches
echo "🔥 Warming caches..."
php artisan cache:clear
php artisan config:clear
php artisan octane:restart || true

# Restart queue workers
echo "🔄 Restarting queue workers..."
php artisan horizon:terminate || true

echo "✅ Deployment complete!"