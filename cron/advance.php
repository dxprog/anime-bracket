<?php

require('lib/aal.php');

// Disable caching
Lib\Cache::setDisabled(true);

// Get all brackets that need to be updated
$brackets = Api\Bracket::queryReturnAll([ 'advanceHour' => gmdate('G'), 'state' => [ 'in' => [ BS_ELIMINATIONS, BS_VOTING ] ] ]);

foreach ($brackets as $bracket) {
    $bracket->advance();
    Api\Bracket::getBracketByPerma($bracket->perma);
}

// Refresh caches
Api\Bracket::getAll();