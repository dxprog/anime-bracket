<?php

require('lib/aal.php');

$characters = [];
$result = Lib\Db::Query('SELECT c.*, COUNT(1) AS total FROM votes v INNER JOIN `character` c ON c.character_id = v.character_id WHERE c.bracket_id = :bracketId GROUP BY v.character_id ORDER BY total DESC LIMIT 256', array( ':bracketId' => BRACKET_ID ));
while ($row = Lib\Db::Fetch($result)) {
	$characters[] = new Api\Character($row);
}

$rands = array();

$i = 0;
$rounds = 0;
$count = count($characters);
while ($i < $count) {
	if (!isset($characters[$i]->done)) {
		$character1 = $characters[$i];
		$character2Index = array_rand($characters);
		$character2 = $characters[$character2Index];
		while (isset($character2->done) || $character2->characterId == $character1->characterId) {
			$character2Index = array_rand($characters);
			$character2 = $characters[$character2Index];
		}
		$characters[$character2Index]->done = true;
		$characters[$i]->done = true;
		
		$round = new Api\Round();
		$round->bracketId = BRACKET_ID;
		$round->roundTier = 1;
		$round->roundCharacter1Id = $character1->characterId;
		$round->roundCharacter2Id = $character2->characterId;
		$order = rand() % ($count);
		while (isset($rands[$order])) {
			$order = rand() % ($count);
		}
		$rands[$order] = true;
		$round->roundOrder = $order;
		$round->roundGroup = floor($rounds / 32);
		$round->sync();
		$rounds++;
	}
	
	$i++;

}

$rounds = Api\Round::getBracketRounds(BRACKET_ID, 1, 0);
$xml = Lib\SerializeXML::serialize($rounds);
echo $xml;