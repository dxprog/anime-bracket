<?php

namespace Controller {

    use Api;
    use Lib;
    use stdClass;

    class Vote extends Page {

        public static function generate(array $params) {

            $user = self::_checkLogin();
            self::_enableAd();

            $perma = array_shift($params);
            $bracket = Api\Bracket::getBracketByPerma($perma);

            if ($bracket->start <=  time() && ($bracket->state == BS_ELIMINATIONS || $bracket->state == BS_VOTING || $bracket->state == BS_WILDCARD)) {
                $cacheKey = 'CurrentRound_' . $bracket->id . '_' . $user->id;
                $out = Lib\Cache::getInstance()->fetch(function() use ($user, $bracket) {
                    $out = new stdClass;
                    $out->userId = $user->id;
                    $out->round = Api\Round::getCurrentRounds($bracket->id);
                    $out->title = Api\Round::getBracketTitleForActiveRound($bracket);
                    return $out;
                }, $cacheKey, CACHE_MEDIUM);

                if ($out) {
                    $out->bracket = $bracket;
                    $out->showCaptcha = false;
                    $out->csrfToken = $user->csrfToken;

                    $template = $out->bracket->state == BS_ELIMINATIONS ? 'eliminations' : 'voting';

                    switch ($bracket->captcha) {
                        case Api\Bracket::$CAPTCHA_STATUS['RANDOM']:
                            $out->showCaptcha = rand() > 0.5;
                            break;
                        case Api\Bracket::$CAPTCHA_STATUS['ALWAYS']:
                            $out->showCaptcha = true;
                            break;
                    }

                    if ($bracket->state != BS_ELIMINATIONS) {
                        $entrantSwap = Lib\TestBucket::get('entrantSwap');
                        if ($entrantSwap !== 'control') {
                            foreach ($out->round as $round) {
                                // Interesting side effect that I had not considered before:
                                // When TestBucket initializes, it's setting the random seed for the entire RNG (duh).
                                // That means the following random line will produce a static set of results, so the
                                // user experience won't be wonky.
                                if ($entrantSwap === 'flip' || ($entrantSwap === 'random' && rand() % 2 === 0)) {
                                    $round = self::_flipEntrants($round);
                                }
                            }
                        }
                    }

                    Lib\Display::addKey('page', 'vote');
                    Lib\Display::addKey('title', $bracket->name . ' - Voting' . DEFAULT_TITLE_SUFFIX);
                    Lib\Display::addKey('bracketNav', $bracket);
                    Lib\Display::renderAndAddKey('content', $template, $out);
                }
            }

        }

        // Honestly surprised PHP doesn't have a swap_var function when they've got shit for suntimes >_>
        private static function _flipEntrants(Api\Round $round) {
            $char = $round->character1;
            $charId = $round->character1Id;
            $charVotes = $round->character1Votes;
            $round->character1 = $round->character2;
            $round->character1Id = $round->character2Id;
            $round->character1Votes = $round->character2Votes;
            $round->character2 = $char;
            $round->character2Id = $charId;
            $round->character2Votes = $charVotes;
        }

    }

}