# Stage 1: Build frontend assets with Vite
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

# Stage 2: Production PHP-FPM runtime
FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        curl \
        icu-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        libxml2-dev \
        mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Production PHP & OPcache configuration (supports 5MB+ Excel imports)
RUN { \
        echo "expose_php=Off"; \
        echo "memory_limit=512M"; \
        echo "upload_max_filesize=10M"; \
        echo "post_max_size=12M"; \
        echo "max_execution_time=120"; \
        echo "opcache.enable=1"; \
        echo "opcache.memory_consumption=128"; \
        echo "opcache.interned_strings_buffer=16"; \
        echo "opcache.max_accelerated_files=20000"; \
        echo "opcache.validate_timestamps=0"; \
    } > /usr/local/etc/php/conf.d/99-production.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN rm -f bootstrap/cache/*.php \
    && composer dump-autoload --optimize --no-dev \
    && mkdir -p /var/www/public-init \
    && cp -a /var/www/html/public/. /var/www/public-init/ \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

RUN cat <<'EOF' > /usr/local/bin/docker-entrypoint-prod.sh
#!/usr/bin/env bash
set -e

# Sync public assets (including Vite build) to the shared volume for Nginx
if [ -d /var/www/public-init ]; then
    cp -a /var/www/public-init/. /var/www/html/public/
fi

mkdir -p \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

php artisan storage:link --force >/dev/null 2>&1 || true

# Wait for MySQL to accept connections on PC reboot before running migrations
if [ -n "${DB_HOST:-}" ]; then
    for i in $(seq 1 30); do
        if php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }' >/dev/null 2>&1; then
            break
        fi
        sleep 2
    done
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
EOF
RUN chmod +x /usr/local/bin/docker-entrypoint-prod.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-prod.sh"]
CMD ["php-fpm"]

