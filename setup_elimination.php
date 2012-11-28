<?php

require('lib/aal.php');

$characters = [];
$result = Lib\Db::Query('SELECT * FROM `character` WHERE bracket_id = 3 ORDER BY RAND()');
while ($row = Lib\Db::Fetch($result)) {
	$characters[] = new Api\Character($row);
}

$i = 0;
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
		$round->bracketId = 3;
		$round->roundCharacter1Id = $character1->characterId;
		$round->roundCharacter2Id = $character2->characterId;
		$round->roundOrder = rand() % ($count - $i);
		$round->sync();
	}
	
	$i++;

}

print_r($characters);

$rounds = Api\Round::getBracketRounds(3, 0);
$xml = Lib\SerializeXML::serialize($rounds);
echo $xml;