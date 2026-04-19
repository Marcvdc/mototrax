FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    postgresql-client \
    libpq-dev \
    && docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd zip intl \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory permissions
COPY . /var/www/html

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod 1777 /tmp

# Copy and set up entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Change current user to www
USER www-data

# Expose port 9000 and start php-fpm server
EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
