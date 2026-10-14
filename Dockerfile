# --- bundled Ploi API: dependencies ---
FROM composer:2 AS api-vendor
WORKDIR /app
COPY api/composer.json api/composer.lock ./
# Extensions are provided by the runtime image; the lock file is authoritative.
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --no-progress --ignore-platform-reqs
COPY api/ ./
RUN composer dump-autoload --optimize --no-dev

# --- panel: dependencies ---
FROM composer:2 AS panel-vendor
WORKDIR /app
COPY panel/composer.json panel/composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --no-progress --ignore-platform-reqs
COPY panel/ ./
RUN composer dump-autoload --optimize --no-dev

# --- panel frontend assets (Vite) ---
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY panel/package.json panel/package-lock.json ./
RUN npm ci
COPY panel/ ./
# Filament's admin theme imports/scans files from the composer vendor tree
COPY --from=panel-vendor /app/vendor/filament /app/vendor/filament
COPY --from=panel-vendor /app/vendor/saade /app/vendor/saade
RUN npm run build

# --- runtime: one Caddy (FrankenPHP), both apps ---
FROM dunglas/frankenphp:1-php8.4-bookworm

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql zip bcmath intl pcntl gd exif

COPY --from=api-vendor /app /var/www/api
COPY --from=panel-vendor /app /var/www/panel
COPY --from=assets /app/public/build /var/www/panel/public/build

RUN mkdir -p /var/www/api/storage/framework/cache/data /var/www/api/storage/framework/sessions /var/www/api/storage/framework/views /var/www/api/storage/logs /var/www/api/bootstrap/cache \
    /var/www/panel/storage/framework/cache/data /var/www/panel/storage/framework/sessions /var/www/panel/storage/framework/views /var/www/panel/storage/logs /var/www/panel/bootstrap/cache /var/www/panel/storage/app/public \
    && ln -s ../storage/app/public /var/www/panel/public/storage

COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=pgsql \
    SESSION_DRIVER=cookie \
    QUEUE_CONNECTION=sync \
    CACHE_STORE=file \
    MAIL_MAILER=log \
    PLOI_API_URL=http://127.0.0.1:8081/

EXPOSE 80 8081

ENTRYPOINT ["entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
