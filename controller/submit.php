<?php

namespace Controller {

    use Api;
    use Lib;
    use stdClass;

    define('FLOOD_CONTROL', 3);

    class Submit extends Page {

        public static function generate(array $params) {

            $action = Lib\Url::Get('action', null);
            $out = new stdClass;
            $out->success = false;

            $user = Api\User::getCurrentUser();
            if ($user) {

                if (self::_isFlooding($user)) {
                    $out->message = 'You\'re doing that too fast!';
                } else {
                    switch ($action) {
                        case 'nominate':
                            $out = self::_nominate();
                            break;
                        case 'vote':
                            $out = self::_vote($user);
                            break;
                        default:
                            $out->message = 'No action specified';
                            break;
                    }

                    if ($out->success) {
                        self::_setFloodMarker($user);
                    }

                }

            } else {
                $out->message = 'You must be logged in';
            }

            Lib\Display::renderJson($out);

        }

        /**
         * Checks to see if the user is flooding the server with requests too quickly
         */
        private static function _isFlooding($user) {
            $cacheKey = 'FloodGuard_' . $user->id;
            $retVal = Lib\Cache::Get($cacheKey, true);
            return $retVal && $retVal + FLOOD_CONTROL > time();
        }

        private static function _setFloodMarker($user) {
            $cacheKey = 'FloodGuard_' . $user->id;
            Lib\Cache::Set($cacheKey, time(), FLOOD_CONTROL);
        }

        private static function _nominate() {

            $out = new stdClass;
            $out->success = false;

            $bracketId = Lib\Url::Post('bracketId', true);
            $nomineeName = Lib\Url::Post('nomineeName');
            $nomineeSource = Lib\Url::Post('nomineeSource');
            $image = Lib\Url::Post('image');

            if ($bracketId && $nomineeName && $nomineeSource && $image) {

                // Verify the image first
                if (self::_verifyImage($image)) {
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
                    $out->message = 'Invalid image';
                }

            } else {
                $out->message = 'Missing fields';
                $out->data = $_POST;
            }

            return $out;

        }

        private static function _vote($user) {

            $out = new stdClass;
            $out->success = false;

            $bracketId = Lib\Url::Post('bracketId', true);

            if ($bracketId) {
                $bracket = Api\Bracket::getById($bracketId);
                $state = $bracket ? (int) $bracket->state : null;

                if ($bracket->isLocked()) {
                    $out->message = 'Voting is closed for this round. Please refresh to see the latest round.';
                } else if ($state === BS_ELIMINATIONS || $state === BS_VOTING) {

                    // Break the votes down into an array of round/character objects
                    $votes = [];
                    foreach($_POST as $key => $val) {
                        if (strpos($key, 'round:') === 0) {
                            $key = str_replace('round:', '', $key);
                            $obj = new stdClass;
                            $obj->roundId = (int) $key;
                            $obj->characterId = (int) $val;
                            $votes[] = $obj;
                        }
                    }

                    $count = count($votes);
                    if ($count > 0) {

                        $query = 'INSERT INTO `votes` (`user_id`, `vote_date`, `round_id`, `character_id`, `bracket_id`) VALUES ';
                        $params = [ ':userId' => $user->id, ':date' => time(), ':bracketId' => $bracketId ];

                        $insertCount = 0;

                        // Only run an insert for rounds that haven't been voted on
                        $rounds = Api\Votes::getOpenRounds($user, $votes);

                        for ($i = 0; $i < $count; $i++) {
                            if (!isset($rounds[$votes[$i]->roundId])) {
                                $query .= '(:userId, :date, :round' . $i . ', :character' . $i . ', :bracketId),';
                                $params[':round' . $i] = $votes[$i]->roundId;
                                $params[':character' . $i] = $votes[$i]->characterId;
                                $insertCount++;
                                $rounds[$votes[$i]->roundId] = true;
                            }
                        }

                        if ($insertCount > 0) {
                            $query = substr($query, 0, strlen($query) - 1);
                            if (Lib\Db::Query($query, $params)) {
                                $out->success = true;
                                $out->message = 'Your votes were successfully submitted!';

                                // Clear any user related caches
                                $round = Api\Round::getById($votes[0]->roundId);
                                Lib\Cache::Set('GetBracketRounds_' . $bracketId . '_' . $round->tier . '_' . $round->group . '_' . $user->id, false);
                                Lib\Cache::Set('GetBracketRounds_' . $bracketId . '_' . $round->tier . '_all_' . $user->id, false);
                                Lib\Cache::Set('CurrentRound_' . $bracketId . '_' . $user->id, false);
                                $bracket->getVotesForUser($user, true);
                            } else {
                                $out->message = 'There was an unexpected error. Please try again in a few moments.';
                            }

                        } else {
                            $out->message = 'Voting for this round has closed';
                            $out->code = 'closed';
                        }

                    } else {
                        $out->message = 'No votes were submitted';
                    }
                } else {
                    $out->message = 'Voting is closed on this bracket';
                    $out->code = 'closed';
                }
            } else {
                $out->message = 'Invalid parameters';
            }

            return $out;

        }

        private static function _verifyImage($url) {
            $headers = @get_headers($url, true);
            return isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'image/') !== false;
        }

    }

}