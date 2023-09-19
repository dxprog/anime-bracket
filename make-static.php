<?php

require_once('app-config.php');
chdir(CORE_LOCATION);
require_once('config.php');

// Include base libraries
require_once('./lib/aal.php');

$brackets = Api\Bracket::Query(['state' => BS_FINAL]);

while ($bracket = Lib\Db::Fetch($brackets)) {
  $perma = $bracket->bracket_perma;
  $path = CORE_LOCATION . '/baked/' . $bracket->bracket_perma;
  if (!file_exists($path)) {
    mkdir($path);
  }
  Controller\Results::render([$perma]);
  file_put_contents($path . '/results.html', Lib\Display::renderToString());

  Controller\Characters::render([$perma]);
  file_put_contents($path . '/characters.html', Lib\Display::renderToString());

  Controller\Stats::render([$perma]);
  file_put_contents($path . '/stats.html', Lib\Display::renderToString());
  exit;
}
