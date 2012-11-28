<?php

require('lib/aal.php');

$action = Lib\Url::Get('action');

// Set the time zone
date_default_timezone_set('America/Chicago');

$out = new stdClass;
$out->success = false;

// Check for flooding
$cacheKey = 'AwwnimeBracket_' . $_SERVER['REMOTE_ADDR'];
$time = Lib\Cache::Get($cacheKey);

if ($time > 0 && time() - $time < 3) {
	$out->message = 'You\'re doing that too fast';
} else {

	switch ($action) {

		case 'nominate':
			
			$bracketId = Lib\Url::Post('bracketId', true);
			$nomineeName = Lib\Url::Post('nomineeName');
			$nomineeSource = Lib\Url::Post('nomineeSource');
			
			if ($bracketId && $nomineeName && $nomineeSource) {
				$nominee = new Api\Nominee();
				$nominee->setBracketId($bracketId);
				$nominee->setNomineeName($nomineeName);
				$nominee->setNomineeSource($nomineeSource);
				$nominee->setNomineeCreated(time());
				if ($nominee->sync()) {
					$out->success = true;
				} else {
					$out->message = 'Unable to save to database';
				}
			} else {
				$out->message = 'Missing fields';
				$out->data = $_POST;
			}
			
			break;
		case 'vote':
			
			$bracketId = Lib\Url::Post('bracketId', true);
			$votes = Lib\Url::Post('votes');
			
			if ($bracketId && $votes) {
				$votes = explode(',', $votes);
				$count = count($votes);
				if ($count > 0 && $count % 2 === 0) {
					
					$query = 'INSERT INTO `votes` (`vote_ip`, `vote_date`, `round_id`, `character_id`) VALUES ';
					session_start();
					$ip = $_SERVER['REMOTE_ADDR'];
					$params = array( ':ip' => $ip, ':date' => time() );
					$insertCount = 0;
					for ($i = 0, $count = count($votes); $i < $count; $i += 2) {
						$row = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(1) AS total FROM votes WHERE vote_ip = :ip AND round_id = :round', array( ':ip'=>$ip, ':round' => $votes[$i] )));
						if ((int)$row->total == 0) {
							$query .= '(:ip, :date, :round' . $i . ', :character' . $i . '),';
							$params[':round' . $i] = $votes[$i];
							$params[':character' . $i] = $votes[$i + 1];
							$insertCount++;
						}
					}
					
					if ($insertCount > 0) {
						$query = substr($query, 0, strlen($query) - 1);
						Lib\Db::Query($query, $params);
					}
					$out->success = true;
					
					// Clear any user related caches
					$round = Api\Round::getRoundById($votes[0]);
					Lib\Cache::Set('GetBracketRounds_' . $bracketId . '_' . $round->roundTier . '_' . $round->roundGroup . '_' . $ip, false);
					Lib\Cache::Set('CurrentRound_' . $bracketId . '_' . $_SERVER['REMOTE_ADDR'], false);
					
				} else {
					$out->message = 'No votes were submitted';
				}
			} else {
				$out->message = 'Invalid parameters';
			}
			
			break;
			
		default:
			$out->message = 'Invalid command';
			break;

	}
	
	Lib\Cache::Set($cacheKey, time(), 5);
	
}
echo json_encode($out);