<?php

require_once('app-config.php');
chdir(CORE_LOCATION);
require_once('config.php');
require_once('lib/aal.php');

// Get all brackets that need to be updated
$brackets = Api\Bracket::queryReturnAll([ 'state' => [ 'in' => [ BS_NOMINATIONS, BS_ELIMINATIONS, BS_VOTING ] ] ]);

$bot = new Api\Reddit(RB_BOT);

foreach ($brackets as $bracket) {
  $cacheKey = 'CRON::reddit-poster_' . $bracket->id;
  $title = Api\Round::getBracketTitleForActiveRound($bracket);
  $oldTitle = Lib\Cache::Get($cacheKey);
  if ($title != $oldTitle) {
    $fullTitle = $bracket->name . ' - ' . $title;
    $message = '[Vote on today\'s round](http://animebracket.com/vote/' . $bracket->perma . ')' . PHP_EOL . PHP_EOL . '[View bracket results](http://animebracket.com/results/' . $bracket->perma . ')';
    $out = null;
    if ($bot->Submit($fullTitle, $message, 'r/AnimeBracket', 'self', $out)) {
      $bracket->externalId = $out->data->id;
      $bracket->sync();
    }
    sleep(10);
  }
  Lib\Cache::Set($cacheKey, $title, 3600);
}