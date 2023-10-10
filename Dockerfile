# Static file builds
FROM node:14-alpine AS node-build

WORKDIR /build

COPY package.json .
COPY package-lock.json .
COPY .babelrc .
COPY webpack.config.js .
COPY static/ static/
COPY views/ views/

RUN npm ci
RUN npm run build

# PHP web app
FROM php:7.4-fpm

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions @composer gd opcache memcached pdo_mysql redis

WORKDIR /app
COPY --from=node-build /build/dist/* /static

COPY . .

RUN composer install --prefer-source --no-interaction
