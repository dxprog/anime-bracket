<?php

require('lib/aal.php');
session_start();

$action = Lib\Url::Get('action');

// Set the time zone
date_default_timezone_set('America/Chicago');

$out = new stdClass;
$out->success = false;

// Check for flooding
$cacheKey = 'AwwnimeBracket_' . $_SERVER['REMOTE_ADDR'];
$time = Lib\Cache::Get($cacheKey);

$user = Api\User::getCurrentUser();
if ($user) {
	if ($time > 0 && time() - $time < 3) {
		$out->message = 'You\'re doing that too fast';
	} else {

		switch ($action) {

			case 'nominate':

				$bracketId = Lib\Url::Post('bracketId', true); //Lib\Url::Post('bracketId', true);
				$nomineeName = Lib\Url::Post('nomineeName');
				$nomineeSource = Lib\Url::Post('nomineeSource');
				$image = Lib\Url::Post('image');

				if ($bracketId && $nomineeName && $nomineeSource && $image) {
					$nominee = new Api\Nominee();
					$nominee->bracketId = $bracketId;
					$nominee->name = $nomineeName;
					$nominee->source = $nomineeSource;
					$nominee->created = time();
					$nominee->image = $image;
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
				$prizes = Lib\Url::Post('prizes') === 'true';

				if ($bracketId && $votes) {
					$bracket = Api\Bracket::getById($bracketId);
					$state = $bracket ? (int) $bracket->state : null;
					if ($state === BS_ELIMINATIONS || $state === BS_NOMINATIONS || $state === BS_VOTING) {
						$votes = explode(',', $votes);
						$count = count($votes);
						if ($count > 0 && $count % 2 === 0) {

							$query = 'INSERT INTO `votes` (`user_id`, `vote_date`, `round_id`, `character_id`, `bracket_id`) VALUES ';
							$params = [ ':userId' => $user->id, ':date' => time(), ':bracketId' => $bracketId ];

							$insertCount = 0;
							for ($i = 0, $count = count($votes); $i < $count; $i += 2) {
								$row = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(1) AS total FROM votes WHERE user_id = :userId AND round_id = :round', [ ':userId' => $user->id, ':round' => $votes[$i] ]));
								if ((int) $row->total === 0) {
									$query .= '(:userId, :date, :round' . $i . ', :character' . $i . ', :bracketId),';
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
							$round = Api\Round::getById($votes[0]);
							Lib\Cache::Set('GetBracketRounds_' . $bracketId . '_' . $round->tier . '_' . $round->group . '_' . $user->id, false);
							Lib\Cache::Set('CurrentRound_' . $bracketId . '_' . $user->id, false);

							// Save the user's prize preference (if it's changed)
							if ((bool) $user->prizes !== $prizes) {
								$user->prizes = $prizes;
								$user->sync();
							}

						} else {
							$out->message = 'No votes were submitted';
						}
					} else {
						$out->message = 'Voting is closed on this bracket';
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
} else {
	$out->message = 'Uh oh... it seems you\'re not logged in';
}

echo json_encode($out);