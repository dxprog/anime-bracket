<?php

require('lib/aal.php');

define('AI_BRACKET_ID', 6);

// 872 - Ayase
// 1088 - Tamako

// Returns the percentage of users who have voted for char1 but not char2
function getLoyalists($char1, $char2) {

    $retVal = new stdClass;
    $retVal->voterCount = $char1->getVoterCount();

    $params = [ ':char1' => $char1->id, ':char2' => $char2->id ];
    $result = Lib\Db::Query('SELECT COUNT(DISTINCT v.user_id) AS total FROM votes v INNER JOIN round r ON r.round_id = v.round_id AND r.round_final = 1 WHERE r.round_tier > 0 AND v.user_id IN (SELECT DISTINCT user_id FROM votes WHERE character_id = :char1) AND user_id NOT IN (SELECT DISTINCT user_id FROM votes WHERE character_id = :char2)', $params);
    if ($result && $result->count === 1) {
        $row = Lib\Db::Fetch($result);
        $retVal->percent = (int) $row->total / $retVal->voterCount;
    }

    return $retVal;

}

// Returns an array of user IDs that voted on both $char1 and $char2
function getNonLoyalists($char1, $char2) {

    $retVal = [];
    $params = [ ':char1' => $char1->id, ':char2' => $char2->id ];
    $result = Lib\Db::Query('SELECT DISTINCT v.user_id FROM votes v INNER JOIN round r ON r.round_id = v.round_id WHERE r.round_tier > 0 AND r.round_final = 1 AND v.user_id IN (SELECT DISTINCT user_id FROM votes WHERE character_id IN (:char1, :char2))', $params);
    if ($result && $result->count)   {
        while ($row = Lib\Db::Fetch($result)) {
            $retVal[] = (int) $row->user_id;
        }
    }

    return $retVal;

}

// Returns an array of character IDs that are in a source
function getSourceCharacters($source) {
    $result = Lib\Db::Query('SELECT character_id FROM `character` WHERE bracket_id = :bracket AND character_source = :source', [ ':bracket' => AI_BRACKET_ID, ':source' => $source ]);
    $retVal = [];
    if ($result && $result->count) {
        while ($row = Lib\Db::Fetch($result)) {
            $retVal[] = (int) $row->character_id;
        }
    }
    return $retVal;
}

// Returns an array of round IDs that have characters from $source
function getSourceRounds($source) {

    $retVal = [];
    $charIds = implode(',', getSourceCharacters($source));
    $result = Lib\Db::Query('SELECT round_id FROM round WHERE round_tier > 0 AND round_final = 1 AND round_character1_id IN (' . $charIds . ') OR round_character2_id IN (' . $charIds . ')');
    if ($result && $result->count) {
        while ($row = Lib\Db::Fetch($result)) {
            $retVal[] = (int) $row->round_id;
        }
    }

    return $retVal;

}

function getSourceTotalVotes($char, $rounds) {
    $rounds = implode(',', getSourceRounds($char->source));
    $result = Lib\Db::Query('SELECT COUNT(1) AS total FROM votes v INNER JOIN round r ON r.round_id = v.round_id WHERE v.round_id IN (' . $rounds . ')');
}

// Returns the percentage of voters (within a user subset) that voted for the character's source
function getSourceStrength($char, $users) {

    $retVal = new stdClass;

    // Get the total number of votes that the source rounds have garnered
    $rounds = implode(',', getSourceRounds($char->source));
    $charIds = implode(',', getSourceCharacters($char->source));
    $users = implode(',', $users);
    $result = Lib\Db::Query('SELECT COUNT(1) AS total FROM votes v INNER JOIN round r ON r.round_id = v.round_id WHERE v.round_id IN (' . $rounds . ') AND v.user_id IN (' . $users . ')');
    if ($result && $result->count) {
        $row = Lib\Db::Fetch($result);
        $retVal->sourceTotal = (int) $row->total;

        // Get the percentage that voted for the source
        $result = Lib\Db::Query('SELECT COUNT(1) AS total FROM votes v INNER JOIN round r ON r.round_id = v.round_id WHERE v.round_id IN (' . $rounds . ') AND v.user_id IN (' . $users . ') AND v.character_id IN (' . $charIds . ')');
        if ($result && $result->count === 1) {
            $row = Lib\Db::Fetch($result);
            $retVal->sourcePercentage = (int) $row->total / $retVal->sourceTotal;
        }

    }

    return $retVal;

}

function getPercentTimesVotedFor($char, $users) {

    $retVal = 0;
    $query = 'SELECT round_id FROM round WHERE round_character1_id = :id OR round_character2_id = :id';
    $result = Lib\Db::Query('SELECT COUNT(1) AS total, SUM(CASE WHEN character_id = :id THEN 1 ELSE 0 END) AS character_votes FROM votes WHERE round_id IN (' . $query . ') AND user_id IN (' . implode(',', $users) . ')', [ ':id' => $char->id ]);
    if ($result && $result->count) {
        $row = Lib\Db::Fetch($result);
        $retVal = (int) $row->character_votes / (int) $row->total;
    }

    return $retVal;

}

// The voter sample
$sampleSize = 10000;

$projections = [];

// Get the current voting round
$rounds = Api\Round::getRoundsByGroup(AI_BRACKET_ID, 4, 1);
foreach ($rounds as $round) {

    $char1 = Api\Character::getById($round->character1Id);
    $char2 = Api\Character::getById($round->character2Id);

    $nonLoyalists = getNonLoyalists($char1, $char2);

    $char1->loyalists = getLoyalists($char1, $char2);
    $char2->loyalists = getLoyalists($char2, $char1);

    $char1->sourceStrength = getSourceStrength($char1, $nonLoyalists);
    $char2->sourceStrength = getSourceStrength($char2, $nonLoyalists);

    $char1->percentOfTimesVotedFor = getPercentTimesVotedFor($char1, $nonLoyalists);
    $char2->percentOfTimesVotedFor = getPercentTimesVotedFor($char2, $nonLoyalists);

    $nonLoyalistPercentage = 1 - ($char1->loyalists->percent + $char2->loyalists->percent);
    $bestCharacter = $char1->percentOfTimesVotedFor > $char2->percentOfTimesVotedFor ? $char1 : $char2;
    $worstCharacter = $char1->percentOfTimesVotedFor > $char2->percentOfTimesVotedFor ? $char2 : $char1;

    $popularityRatio = $bestCharacter->percentOfTimesVotedFor / $worstCharacter->percentOfTimesVotedFor;
    $worstCharacterNonAdjusted = $nonLoyalistPercentage / ($popularityRatio + 1);
    $worstCharacter->projectedPercentage = $worstCharacterNonAdjusted + $worstCharacter->loyalists->percent;
    $bestCharacter->projectedPercentage = $nonLoyalistPercentage - $worstCharacterNonAdjusted + $bestCharacter->loyalists->percent;

    $numbers = new stdClass;
    $numbers->nonLoyalistPercentage = $nonLoyalistPercentage;
    $numbers->popularityRatio = $popularityRatio;

    $char1 = json_decode(json_encode($char1));
    $char2 = json_decode(json_encode($char2));

    $diff = abs($char1->projectedPercentage - $char2->projectedPercentage);

    if ($diff < 0.04) {
        $projections[] = '- ' . $char1->name . ' vs ' . $char2->name . ' - Too close to call (' . $bestCharacter->name . ' - ' . $bestCharacter->projectedPercentage . ')';
    } else {
        if ($char1->projectedPercentage < $char2->projectedPercentage) {
            $projections[] = '- ' . $char1->name . ' vs **' . $char2->name . '** - With ' . round($char2->projectedPercentage * 100) . '% of the votes';
        } else {
            $projections[] = '- **' . $char1->name . '** vs ' . $char2->name . ' - With ' . round($char1->projectedPercentage * 100) . '% of the votes';
        }
    }

}

echo implode(PHP_EOL, $projections), PHP_EOL;