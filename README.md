[![Build Status](https://travis-ci.org/dxprog/anime-bracket.svg)](https://travis-ci.org/dxprog/anime-bracket)

# Brakkit

A site for running standard elimination style brackets.

## Things you'll need to run brakkit
- PHP 5.4+
- MySQL/MariaDB 5.1+
- NodeJS 0.12+ (to build static JavaScript assets)
- memcached
- redis

## Configuration and Installing

### Configuration

Brakkit is set up to allow multiple front-ends run with a single back-end. As such, there are two config files:

- `config.php`: This is the configuration for the back-end system and is primarily database setup. The back-end portion houses the `api/`, `cache/`, `controller/`, `lib/`, and `images/` directories.
- `app-config.php`: Configuration values for an individual front-end. The front-end consists of the `static/` and `views/` directories. Use `app-config.sample.php` to set up your app config.

### Installing

#### Database

To bootstrap your database, use your favorite SQL execution method of choice to run the queries in `database.sql`. You can do so from the commandline as follows:

```
mysql -u USER_NAME -p DATABASE_NAME < bracket.sql
```

At this point, you'll want to set the values in `config.php` to connect to your database.

#### Building Static Assets

You'll need node and npm for the following. If you don't have these, I recommend using [nvm](https://github.com/creationix/nvm) to install these on your system.

Once you've installed that (or if you already have node), run the following to build all the static content:

```
npm install
grunt build
```

This will build the following files that you'll want to ensure is served from your front-end:

```
static/
├─css/
├────anime-bracket.css
├─js/
└────anime-bracket.min.js
```
