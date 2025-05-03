ARG ALPINE_VERSION=3.21

FROM alpine:${ALPINE_VERSION}

# initialize arguments
ARG PHP_VERSION=82
ARG USER_ID=1000
ARG GROUP_ID=1000
ARG WORKDIR=/app/Bot

# install dependencies
RUN apk add \
    bash \
    git \
    icu-data-full \
    php${PHP_VERSION} \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-dom \
    php${PHP_VERSION}-fileinfo \
    php${PHP_VERSION}-iconv \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-openssl \
    php${PHP_VERSION}-pdo \
    php${PHP_VERSION}-pdo_sqlite \
    php${PHP_VERSION}-phar \
    php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-session \
    php${PHP_VERSION}-simplexml \
    php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-tokenizer \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-xmlreader \
    php${PHP_VERSION}-xmlwriter \
    php${PHP_VERSION}-zip \
    && ln -s /usr/bin/php${PHP_VERSION} /usr/bin/php


# add user and group for the app
RUN addgroup -g ${GROUP_ID} bot \
    && adduser -u ${USER_ID} -G bot -D bot \
    && mkdir -p ${WORKDIR}/Storage/Logs \
    && chown -R ${USER_ID}:${GROUP_ID} ${WORKDIR}

# install composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

# set the workdir
WORKDIR ${WORKDIR}

# copy the application
COPY --chown=${USER_ID}:${GROUP_ID} src/ .

# change to the user
USER ${USER_ID}:${GROUP_ID}

# initialize composer
RUN composer install \
    --no-interaction \
    --no-dev \
    --optimize-autoloader

# define the storage volume
VOLUME [ "/app/Bot/Storage" ]

# setting entrypoint
ENTRYPOINT [ "php", "Bot.php" ]
