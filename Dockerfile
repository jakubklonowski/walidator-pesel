FROM php:8.2-cli-alpine

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache git unzip \
 && addgroup -g ${GID} app \
 && adduser -u ${UID} -G app -s /bin/sh -D app

COPY --from=composer/composer:2-bin /composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
 && mkdir -p /app/var /app/vendor /tmp/composer-cache \
 && chown -R app:app /app /tmp/composer-cache \
 && git config --system --add safe.directory /app

ENV COMPOSER_CACHE_DIR=/tmp/composer-cache \
    COMPOSER_MEMORY_LIMIT=-1

WORKDIR /app
USER app

ENTRYPOINT ["entrypoint"]
CMD ["php", "-v"]
