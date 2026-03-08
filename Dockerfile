# ============================================
# Stage 1: Install PHP dependencies
# ============================================
FROM composer:latest AS composer-builder

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-reqs

# ============================================
# Stage 2: Build frontend assets (Vite + Tailwind)
# ============================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources/ ./resources/
COPY app/ ./app/
COPY --from=composer-builder /app/vendor/ ./vendor/

RUN mkdir -p storage/framework/views
RUN npm run build

# ============================================
# Stage 3: Final production image
# ============================================
FROM php:8.2-fpm

LABEL org.opencontainers.image.source="https://github.com/anzalajra/zewalo"
LABEL org.opencontainers.image.description="Zewalo SaaS Rental Platform"

ARG user=www
ARG uid=1000

# Install system deps + nginx + supervisor
RUN apt-get update && apt-get install -y \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libicu-dev \
    zip \
    unzip \
    rsync \
    postgresql-client \
    nginx \
    supervisor \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip intl opcache \
    && pecl install redis && docker-php-ext-enable redis

# Composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create app user
RUN useradd -G www-data,root -u $uid -d /home/$user $user \
    && mkdir -p /home/$user/.composer \
    && chown -R $user:$user /home/$user

WORKDIR /var/www

# Copy application code
COPY . /var/www

# Copy built dependencies from builder stages
COPY --from=composer-builder /app/vendor /var/www/vendor
COPY --from=frontend-builder /app/public/build /var/www/public/build

# Package discovery (safe to fail without .env)
RUN php artisan package:discover --ansi 2>/dev/null || true

# Config files
COPY docker/php/production.ini /usr/local/etc/php/conf.d/production.ini
COPY docker/php/www-custom.conf /usr/local/etc/php-fpm.d/zzz-www-custom.conf
COPY docker/nginx/app.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/zewalo.conf

# Setup nginx sites
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Backup public dir for volume sync on deploy
RUN cp -r /var/www/public /var/www/public-build

# Create directories & set permissions
RUN mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/storage/framework/cache/data \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && mkdir -p /var/www/storage/app/public \
    && mkdir -p /var/www/bootstrap/cache \
    && mkdir -p /var/log/supervisor \
    && chown -R $user:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache \
    && chown -R $user:www-data /var/log/supervisor \
    && chown -R $user:www-data /var/lib/nginx \
    && chown -R $user:www-data /var/log/nginx

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/zewalo.conf"]
