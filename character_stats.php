<?php

require('lib/aal.php');

$db = Lib\Mongo::getDatabase();
$characterRankingInfo = $db->characterRankingInfo;

$result = Lib\Db::Query('SELECT * FROM `character` WHERE bracket_id = 6 AND (SELECT COUNT(1) FROM round WHERE round_tier > 0 AND (round_character1_id = character_id OR round_character2_id = character_id)) > 0');
while ($row = Lib\Db::Fetch($result)) {

    $character = new Api\Character($row);
    echo $character->name, '...';

    $obj = new stdClass;

    $obj->characterId = (int) $character->id;
    $obj->performance = round($character->getAverageRoundPerformance(true) * 100);
    $obj->alsoVotedFor = $character->getCharactersAlsoVotedFor(false, true);
    $obj->sameSourceVotes = $character->getCharactersAlsoVotedFor(true, true);

    // JSON encode/decode the object to strip private keys
    $obj = json_decode(json_encode($obj));

    // Check for this character in the collection so we can update the existing record. This is intentionally
    // done after the JSON bullshit above to make sure the object is correctly represented
    $record = $characterRankingInfo->findOne([ 'characterId' => $character->id ]);
    if ($record) {
        $obj->_id = $record['_id'];
    }

    $characterRankingInfo->save($obj);

    echo 'DONE', PHP_EOL;

}