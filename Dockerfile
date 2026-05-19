FROM composer:2 AS composer_deps

WORKDIR /app

COPY . .

RUN if [ -f composer.json ]; then \
      composer install --no-dev --no-interaction --prefer-dist --no-progress; \
    else \
      mkdir -p vendor && echo "No composer.json detected; skipping composer install."; \
    fi

FROM php:8.2-apache AS runtime

WORKDIR /var/www/html

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

COPY --from=composer_deps /app/.env.example ./.env.example
COPY --from=composer_deps /app/.htaccess ./.htaccess
COPY --from=composer_deps /app/VERSION ./VERSION
COPY --from=composer_deps /app/config ./config
COPY --from=composer_deps /app/public ./public
COPY --from=composer_deps /app/routes ./routes
COPY --from=composer_deps /app/src ./src
COPY --from=composer_deps /app/templates ./templates
COPY --from=composer_deps /app/vendor ./vendor

RUN mkdir -p storage/logs storage/uploads \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
