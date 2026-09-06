FROM php:8.4-apache

# pdo_pgsql is not compiled into the base image. libpq-dev provides the headers it
# builds against; it stays in the image, which is a few MB for a much shorter build.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends libpq-dev; \
    docker-php-ext-install -j"$(nproc)" pdo_pgsql; \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
    : > /etc/apache2/ports.conf

COPY apache/site.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /app
COPY bin ./bin
COPY src ./src
COPY public ./public

ENV PORT=8080
EXPOSE 8080

CMD ["/app/bin/start.sh"]
