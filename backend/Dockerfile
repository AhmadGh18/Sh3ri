# Laravel 12 + PHP 8.4 on Apache, tuned for Render's free tier (512 MB).
# Two layers:
#   1. composer install with only composer.* so it caches across code edits
#   2. copy the app + finalize autoloader
#
# Runtime:
#   - Apache serves /var/www/html/public on Render's $PORT
#   - entrypoint runs migrate --force before handing off to apache2-foreground
FROM php:8.4-apache

# System deps + PHP extensions Laravel needs (pgsql, intl, bcmath, zip)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        zip unzip git curl \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql zip intl bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

# opcache defaults (small production profile — 128MB fits in 512MB total)
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.enable_cli=0'; \
      echo 'opcache.memory_consumption=64'; \
      echo 'opcache.max_accelerated_files=10000'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.interned_strings_buffer=8'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: mod_rewrite + document root → /public
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# 1) Cache-friendly composer step
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# 2) Copy the app + finalize autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R ug+w storage bootstrap/cache

# Render assigns $PORT at runtime; Apache honors it via envvars
ENV PORT=8080
EXPOSE 8080
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -i 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf \
 && echo 'export APACHE_RUN_USER=www-data' >> /etc/apache2/envvars \
 && echo 'export APACHE_RUN_GROUP=www-data' >> /etc/apache2/envvars

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["entrypoint.sh"]
