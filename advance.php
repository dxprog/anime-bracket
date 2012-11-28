<?php

define('COMMIT', true);

if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '68.0.74.99') {
	echo 'Only god can do that';
	exit;
}

function getWinner($round) {
	
	$retVal = (int)$round->char1_votes > (int)$round->char2_votes ? $round->round_character1_id : $round->round_character2_id;
	if ((int)$round->char1_votes == (int)$round->char2_votes) {
		$params = array( ':character1' => $round->round_character1_id, ':character2' => $round->round_character2_id );
		$row = Lib\Db::Fetch(Lib\Db::Query('SELECT (SELECT COUNT(1) FROM votes WHERE character_id = :character1 AND vote_ip NOT LIKE "%b%") AS char1_votes, (SELECT COUNT(1) FROM votes WHERE character_id = :character2 AND vote_ip NOT LIKE "%b%") AS char2_votes', $params));
		$retVal = (int)$row->char1_votes > (int)$row->char2_votes ? $round->round_character1_id : $round->round_character2_id;
	}
	return $retVal;
	
}

require('lib/aal.php');

$row = Lib\Db::Fetch(Lib\Db::Query('SELECT MIN(round_tier) AS round_tier FROM round WHERE round_final = 0'));
$params = array( ':tier' => $row->round_tier);
$row = Lib\Db::Fetch(Lib\Db::Query('SELECT MIN(round_group) AS round_group FROM round WHERE round_tier = :tier AND round_final = 0', $params));
$params[':group'] = $row->round_group;

// Finalize the rounds
if (COMMIT) {
	Lib\Db::Query('UPDATE round SET round_final = 1 WHERE bracket_id = 3 AND round_group = :group AND round_tier = :tier', $params);
}


$result = Lib\Db::Query('SELECT *, (SELECT COUNT(DISTINCT vote_ip) FROM votes WHERE round_id = r.round_id AND character_id = r.round_character1_id) AS char1_votes, (SELECT COUNT(DISTINCT vote_ip) FROM votes WHERE round_id = r.round_id AND character_id = r.round_character2_id AND vote_ip NOT LIKE "%b%") AS char2_votes FROM round r WHERE r.round_tier = :tier AND r.round_group = :group ORDER BY r.round_order', $params);if ($result->count > 1) {
	$order = 0;
	while ($round1 = Lib\Db::Fetch($result)) {
		
		if (1 == $round1->round_character2_id) {
		
			$params = array( ':group' => $round1->round_group );
			$params[':tier'] = $round1->round_tier;
			$winner = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(DISTINCT v.vote_ip) AS total, v.character_id FROM votes v INNER JOIN round r ON r.round_id = v.round_id WHERE r.round_group = :group AND r.round_tier = :tier GROUP BY v.character_id ORDER BY total DESC LIMIT 1', $params));
			
			$params[':tier'] = $round1->round_tier + 1;
			$row = Lib\Db::Fetch(Lib\Db::Query('SELECT round_id FROM round WHERE round_group != :group AND round_character2_id = 1 AND round_tier = :tier ORDER BY RAND() LIMIT 1', $params));
			$params = array( ':winner' => $winner->character_id, ':id' => $row->round_id );
			print_r($params);
			if (COMMIT) {
				Lib\Db::Query('UPDATE round SET round_character2_id = :winner WHERE round_id = :id', $params);
			}
			$result = null;
		
		} else {
		
			$character1 = getWinner($round1);
			$round2 = Lib\Db::Fetch($result);
			$character2 = getWinner($round2);
			
			$char1 = Api\Character::getCharacterById($character1);
			$char2 = Api\Character::getCharacterById($character2);
			
			$round = new Api\Round();
			$round->bracketId = 3;
			$round->roundCharacter1Id = $character1;
			$round->roundCharacter2Id = $character2;
			$round->roundTier = $round1->round_tier + 1;
			$round->roundGroup = $round1->round_group;
			$round->roundOrder = $order;
			if (COMMIT) {
				$round->sync();
			}
			$order++;
			
			echo $char1->characterName . ' -vs- ' . $char2->characterName, PHP_EOL;
			
		}
		
	}
} else if ($result->count === 1) {
	// wildcard
	
	$round1 = Lib\Db::Fetch($result);
	$character1 = getWinner($round1);
	
	$char1 = Api\Character::getCharacterById($character1);
	
	$round = new Api\Round();
	$round->bracketId = 3;
	$round->roundCharacter1Id = $character1;
	$round->roundCharacter2Id = 1;
	$round->roundTier = $round1->round_tier + 2;
	$round->roundGroup = $round1->round_group;
	$round->roundOrder = 0;
	if (COMMIT) {
		$round->sync();
	}
	
	// Setup the wildcard round
	unset($params[':tier']);
	$result = Lib\Db::Query('SELECT round_character1_id, round_character2_id FROM round WHERE round_tier = 1 AND round_group = :group', $params);
	$characters = array();
	while ($row = Lib\Db::Fetch($result)) {
		if ($row->round_character1_id != $character1 && !isset($characters[$row->round_character1_id])) {
			$characters[$row->round_character1_id] = true;
		}
		
		if ($row->round_character2_id != $character1 && !isset($characters[$row->round_character2_id])) {
			$characters[$row->round_character2_id] = true;
		}
	}
	
	$order = 0;
	foreach ($characters as $id => $bool) {
		$round = new Api\Round();
		$round->bracketId = 3;
		$round->roundCharacter1Id = $id;
		$round->roundCharacter2Id = 1;
		$round->roundTier = $round1->round_tier + 1;
		$round->roundGroup = $round1->round_group;
		$round->roundOrder = $order;
		if (COMMIT) {
			$round->sync();
		}
		$order++;
	}
	
}