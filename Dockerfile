FROM php:8.4.4-cli-bookworm AS os
LABEL "app"="essa"

RUN apt-get update && apt-get install -y git \
    libzip-dev unzip
RUN docker-php-ext-install -j$(nproc) zip \
 && docker-php-source delete

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /build
RUN useradd -u 1000 -g 0 -m -s /bin/bash app \
 && chown -R app:root /build \
 && chmod -R g+w /build

USER app


# >> Download dependencies
FROM os AS vendor-dev
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-autoloader --no-scripts


# >> Development version (mounted volume), 'dev container'
FROM os AS app-dev
WORKDIR /app
ENTRYPOINT ["./entrypoint.sh"]
CMD ["tail", "-f", "/dev/null"]


# >> Test version (CI/CD), 'test container'
FROM os AS app-test
WORKDIR /app
COPY --chown=app:root . .
COPY --from=vendor-dev --chown=app:root /build/vendor ./vendor
RUN composer dump-autoload
