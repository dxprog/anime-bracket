###
# ENV SET UP
###
FROM debian:bullseye

# Install server things
RUN apt update && \
    apt install -y nodejs \
                   npm \
                   php7.4 \
                   php7.4-fpm \
                   php7.4-gd \
                   php7.4-curl \
                   php7.4-memcached \
                   php7.4-pdo \
                   php7.4-mysql \
                   php7.4-zip \
                   php7.4-cli \
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

# create the writeable directories
RUN mkdir ./cache
RUN chmod 777 ./cache
RUN mkdir ./images
RUN chmod 777 ./images

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
    mysql -D ${DB_NAME} < animebracket.sql && \
    mysql -e "CREATE USER '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASS}'" && \
    mysql -e "GRANT SELECT, UPDATE, INSERT, DELETE, EXECUTE ON ${DB_NAME}.* TO '${DB_USER}'@'${DB_HOST}'; FLUSH PRIVILEGES;"

# we don't need the giant dump file hanging around
RUN rm animebracket.sql &2> /dev/null

# setup the auto-advance cron
RUN echo "*/60 *  * * *   root    cd /app && php cron/advance.php" >> /etc/crontab

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
