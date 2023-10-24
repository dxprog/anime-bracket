###
# ENV SET UP
###
FROM debian:bookworm

# Install server things
RUN apt update && \
    apt install -y nodejs \
                   npm \
                   php8.2 \
                   php8.2-fpm \
                   php8.2-gd \
                   php8.2-curl \
                   php8.2-memcached \
                   php8.2-pdo \
                   php8.2-mysql \
                   php8.2-zip \
                   php8.2-cli \
                   mariadb-server \
                   nginx \
                   memcached \
                   redis \
                   git \
                   nano

# Copy nginx config
COPY docker/nginx.conf /etc/nginx/sites-enabled/default

# Get composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === 'e21205b207c3ff031906575712edab6f13eb0b361f2085f1f1237b7126d785e826a450292b6cfd1d64d92e6563bbde02') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer

###
# APP SET UP
###

WORKDIR /app

# create the cache dir
RUN mkdir ./cache
RUN chmod 777 ./cache

COPY . .

# Install PHP package deps
RUN composer install

# Build static assets
RUN npm ci --legacy-peer-deps
RUN npm run build
# move Jcrop to the public static dir
RUN mkdir ./dist/static/js/
RUN cp ./static/js/*.js ./dist/static/js/

# Database setup
ARG DB_HOST
ARG DB_NAME
ARG DB_PASS
ARG DB_USER

RUN service mariadb start && \
    mysql -e "CREATE DATABASE ${DB_NAME};" && \
    mysql -D ${DB_NAME} < database.sql && \
    mysql -e "CREATE USER '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASS}'" && \
    mysql -e "GRANT SELECT, UPDATE, INSERT, DELETE, EXECUTE ON ${DB_NAME}.* TO '${DB_USER}'@'${DB_HOST}'; FLUSH PRIVILEGES;"

# we don't need the giant dump file hanging around
RUN rm anime_bracket.sql &2> /dev/null

# generate the config files from the provided env vars
# just some weird hurdles to make .env the single source
# of truth for secrets and such
ARG HTTP_UA
ARG HANDLE_EXCEPTIONS
ARG CORE_LOCATION
ARG BRACKET_SOURCE
ARG DEFAULT_CONTROLLER
ARG DEFAULT_TITLE
ARG DEFAULT_TITLE_SUFFIX
ARG MAX_WIDTH
ARG MAX_HEIGHT
ARG BRACKET_IMAGE_SIZE
ARG REDDIT_TOKEN
ARG REDDIT_SECRET
ARG REDDIT_HANDLER
ARG REDDIT_MINAGE
ARG IMAGE_LOCATION
ARG IMAGE_URL
ARG REDIS_SERVER
ARG CACHE_PREFIX
ARG USE_MIN
ARG CSS_VERSION
ARG JS_VERSION
ARG SESSION_DOMAIN
ARG VIEW_PATH
ARG LANDING_FEATURE_BRACKET
ARG MAX_USERS_SHARING_IP
ARG RECAPTCHA_SECRET
ARG CANONICAL_DOMAIN

RUN node docker/config-gen.js

EXPOSE 80
RUN chmod +x docker/startup.sh

# Run nginx as the perma-command to keep the container from stopping
ENTRYPOINT ["/bin/bash"]
CMD ["docker/startup.sh"]
