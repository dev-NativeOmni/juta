# Stage 1: Build Frontend Assets (Vite)
FROM node:20-alpine AS node_builder
WORKDIR /app
COPY package*.json vite.config.js ./
RUN npm ci || npm install
COPY resources resources/
COPY public public/
RUN npm run build

# Stage 2: Production PHP Runtime (FrankenPHP on Alpine)
FROM dunglas/frankenphp:1-php8.2-alpine

# Install Postgres client and required PHP extensions for Laravel + Supabase
RUN apk add --no-cache libpq-dev postgresql-client \
    && install-php-extensions pdo_pgsql zip bcmath intl opcache pcntl

# Install Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy source code and pre-compiled frontend assets
COPY . .
COPY --from=node_builder /app/public/build public/build
COPY Caddyfile /etc/caddy/Caddyfile

# Install production PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Set directory permissions for Laravel runtime
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ENV SERVER_NAME=":8080"
EXPOSE 8080

# Production boot: optimize caches and run FrankenPHP server
CMD ["sh", "-c", "php artisan storage:link --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && frankenphp run --config /etc/caddy/Caddyfile"]