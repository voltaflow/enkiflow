#!/bin/sh
set -e

# Wait for database to be ready (if needed)
if [ ! -z "$DB_HOST" ]; then
    echo "Waiting for database connection..."
    until php artisan db:monitor > /dev/null 2>&1; do
        echo "Database is unavailable - sleeping"
        sleep 3
    done
    echo "Database is up - continuing..."
fi

# Run optimizations for production
if [ "$APP_ENV" = "production" ]; then
    echo "Running production optimizations..."
    php artisan config:cache
    php artisan event:cache
    # Skip route cache due to dynamic tenant routes
    # php artisan route:cache
    php artisan view:cache
fi

# Run migrations if enabled
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
fi

# Clear any previous Octane state
php artisan octane:stop > /dev/null 2>&1 || true

# Start Octane with Swoole
echo "Starting Laravel Octane with Swoole..."
exec php artisan octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port=8000 \
    --workers=${OCTANE_WORKERS:-auto} \
    --task-workers=${OCTANE_TASK_WORKERS:-auto} \
    --max-requests=${OCTANE_MAX_REQUESTS:-500}