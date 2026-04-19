#!/bin/sh
set -e

# Wait for the database to be ready
echo "Waiting for database..."
until php artisan db:show --json > /dev/null 2>&1; do
    sleep 1
done
echo "Database is ready."

# Run pending migrations
php artisan migrate --force

# Create storage symlink if not present
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link
fi

# Publish Filament assets if missing
if [ ! -f /var/www/html/public/css/filament/filament/app.css ]; then
    php artisan filament:assets
fi

exec "$@"
