FROM php:8.2-fpm

ARG user=zewalo
ARG uid=1000

# Install system dependencies & Postgres lib (libpq-dev wajib untuk pgsql)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (Postgres & Redis)
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip intl \
    && pecl install redis && docker-php-ext-enable redis

# Fix git safe directory warning
RUN git config --global --add safe.directory /var/www

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

WORKDIR /var/www

# Salin codingan
COPY . /var/www

# Set permission folder storage
RUN chown -R $user:$user /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

USER $user

EXPOSE 9000
CMD ["php-fpm"]