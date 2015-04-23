<?php

require('config.php');
require('lib/aal.php');

// Disable caching
Lib\Cache::setDisabled(true);

// Get all brackets that need to be updated
$brackets = Api\Bracket::queryReturnAll([ 'advanceHour' => gmdate('G'), 'state' => [ 'in' => [ BS_ELIMINATIONS, BS_VOTING ] ] ]);

if ($brackets && count($brackets)) {
    foreach ($brackets as $bracket) {
        echo 'Advancing ', $bracket->name, '...';
        $bracket->advance();
        Api\Bracket::getBracketByPerma($bracket->perma);
        echo 'DONE', PHP_EOL;
    }
}

// Reorder the brackets based upon participation
Lib\Db::Query('CALL proc_UpdateBracketScores');

// Clean out old brackets
Lib\Db::Query('CALL proc_CleanBrackets');

// Refresh caches
Api\Bracket::getAll();