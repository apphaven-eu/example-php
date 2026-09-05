# Build the PostgreSQL and process-control extensions, which need the libpq headers.
FROM php:8.4-apache AS ext
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends libpq-dev; \
    docker-php-ext-install -j"$(nproc)" pdo_pgsql pcntl; \
    rm -rf /var/lib/apt/lists/*

FROM php:8.4-apache
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends libpq5; \
    rm -rf /var/lib/apt/lists/*

COPY --from=ext /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=ext /usr/local/etc/php/conf.d/docker-php-ext-pdo_pgsql.ini /usr/local/etc/php/conf.d/
COPY --from=ext /usr/local/etc/php/conf.d/docker-php-ext-pcntl.ini /usr/local/etc/php/conf.d/

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

HEALTHCHECK --interval=10s --timeout=3s --retries=5 --start-period=15s \
    CMD ["php", "-r", "exit(@file_get_contents('http://127.0.0.1:' . (getenv('PORT') ?: '8080') . '/healthz') === 'ok' ? 0 : 1);"]

CMD ["php", "/app/bin/serve.php"]
