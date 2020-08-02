<?php

define('CORE_LOCATION', '/var/www/brakkit');
define('BRACKET_SOURCE', 1);

define('DEFAULT_CONTROLLER', 'landing');
define('DEFAULT_TITLE', 'AnimeBracket - Because sometimes a poll just isn\'t good enough');
define('DEFAULT_TITLE_SUFFIX', ' - AnimeBracket');

define('MAX_WIDTH', 600);
define('MAX_HEIGHT', 500);
define('BRACKET_IMAGE_SIZE', 150);

define('REDDIT_TOKEN', 'reddit_token');
define('REDDIT_SECRET', 'reddit_secret');
define('REDDIT_HANDLER', 'http://reddit_oauth_handler');
define('REDDIT_MINAGE', 2592000);

define('IMAGE_LOCATION', '/path/to/images');
define('IMAGE_URL', 'http://url.to/images');

define('REDIS_SERVER', 'tcp://127.0.0.1:6379');
define('CACHE_PREFIX', 'AnimeBracket');

// Change to true for production to use minified assets
define('USE_MIN', false);
define('CSS_VERSION', '20140816');
define('JS_VERSION', '20131109');

define('SESSION_DOMAIN', '.brakk.it');

// Path to the /view directory (must be a full path, no trailing slash)
define('VIEW_PATH', '/var/www/brakkit/views');

// ID of a bracket to be featured on the landing page
define('LANDING_FEATURE_BRACKET', 1);

// The maximum number of users that can be logged in under the same IP
define('MAX_USERS_SHARING_IP', 5);

// The reCAPTCHA secret key
define('RECAPTCHA_SECRET', 'RECAPTCHA_SECRET');

// The FQ domain that this site is hosted on. Used for "canonical" tags. No trailing slash
define('CANONICAL_DOMAIN', 'https://brakkit.domain');
