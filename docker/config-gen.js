const { writeFileSync } = require('fs');

const CONFIG_SETS = {
  CONFIG_VARS: [
    'DB_HOST',
    'DB_USER',
    'DB_PASS',
    'DB_NAME',
    'HANDLE_EXCEPTIONS',
  ],
  APP_CONFIG_VARS: [
    'CORE_LOCATION',
    'BRACKET_SOURCE',
    'DEFAULT_CONTROLLER',
    'DEFAULT_TITLE',
    'DEFAULT_TITLE_SUFFIX',
    'MAX_WIDTH',
    'MAX_HEIGHT',
    'BRACKET_IMAGE_SIZE',
    'REDDIT_TOKEN',
    'REDDIT_SECRET',
    'REDDIT_HANDLER',
    'REDDIT_MINAGE',
    'IMAGE_LOCATION',
    'IMAGE_URL',
    'REDIS_SERVER',
    'CACHE_PREFIX',
    'USE_MIN',
    'CSS_VERSION',
    'JS_VERSION',
    'SESSION_DOMAIN',
    'VIEW_PATH',
    'LANDING_FEATURE_BRACKET',
    'MAX_USERS_SHARING_IP',
    'RECAPTCHA_SECRET',
    'CANONICAL_DOMAIN',
  ],
};

function writeConfigFile(configFilePath, variableSet) {
  const fileLines = [ '<?php' ];
  CONFIG_SETS[variableSet].forEach(variableName => {
    fileLines.push(`define('${variableName}', ${JSON.stringify(process.env[variableName])});`);
  });
  writeFileSync(configFilePath, fileLines.join('\n'));
}

writeConfigFile('./config.php', 'CONFIG_VARS');
writeConfigFile('./app-config.php', 'APP_CONFIG_VARS');
