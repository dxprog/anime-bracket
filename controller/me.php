<?php

namespace Controller {

    use Api;
    use Lib;
    use stdClass;

    define('AUTH_BASIC', 1);
    define('AUTH_ADMIN', 2);

    class Me extends Page {

        protected static $_user = null;

        public static function generate(array $params) {

            $action = is_array($params) ? array_shift($params) : null;
            Lib\Display::setLayout('admin');

            $user = self::_checkLogin();
            if ($user) {
                self::$_user = $user;
                Lib\Display::addKey('user', $user);

                $message = null;
                $force = false;
                if (Lib\Url::GetBool('created')) {
                    $message = [
                        'message' => 'Bracket was created successfully!',
                        'type' => 'success'
                    ];
                    $force = true;
                }

                if (Lib\Url::GetBool('edited')) {
                    $message = [
                        'message' => 'Bracket was updated successfully!',
                        'type' => 'success'
                    ];
                    $force = true;
                }

                // If there's an action, check for that page controller and use it
                if ($action && class_exists('Controller\\Admin\\' . $action, true)) {
                    call_user_func([ 'Controller\\Admin\\' . $action, 'generate' ], $params);
                } else {
                    // Show the rollup page
                    self::_main($message, $force);
                }

            }

        }

        protected static function _main($message = null, $force = false) {
            $out = new stdClass;
            $out->brackets = Api\Bracket::getUserOwnedBrackets(self::$_user, $force, Lib\Url::Get('all', null) === 'true');

            // If there's no message passed directly, check for one from cache
            $message = !$message ? self::_getStashedMessage() : $message;

            if ($out->brackets) {

                // Sort the brackets by reverse date
                usort($out->brackets, function($a, $b) {
                    return $a->state == BS_FINAL || $a->state > $b->state ? 1 : -1;
                });

                // Get round information for all the brackets
                $bracketIds = array_map(function($bracket) {
                  return $bracket->id;
                }, $out->brackets);

                $bracketRoundData = [];
                $result = Lib\Db::Query('SELECT bracket_id, MIN(round_group) AS current_group, MAX(round_group) AS last_group FROM `round` WHERE bracket_id IN ("' . implode('","', $bracketIds) . '") AND round_final = 0 AND round_deleted = 0 GROUP BY bracket_id');
                if ($result && $result->count) {
                  while ($row = Lib\Db::Fetch($result)) {
                    $bracketRoundData[(int) $row->bracket_id] = $row;
                  }
                }

                // Decorate each bracket with some information about what phase it can
                // safely move to. Mostly this is for eliminations
                foreach ($out->brackets as $bracket) {
                    $bracket->title = Api\Round::getBracketTitleForActiveRound($bracket);
                    $bracket->nextIsFinal = $bracket->title === 'Title Match';

                    // Get the title of the next round
                    $nextRounds = Api\Round::getNextRounds($bracket);
                    $bracket->nextTitle = null;
                    if ($nextRounds) {
                        $bracket->nextTitle = str_replace([ 'Voting - ', 'Eliminations - ' ], '', Api\Round::getBracketTitleForRound($bracket, $nextRounds[0]));
                    }

                    // This is a dumb catch all while I work out issues in the stored procedure
                    $bracket->nextTitle = $bracket->nextTitle ?: 'Next Round';

                    if ($bracket->state == BS_ELIMINATIONS && isset($bracketRoundData[$bracket->id])) {
                        $row = $bracketRoundData[$bracket->id];

                        // If the eliminations are on the last group, don't show the
                        // advance button
                        if ($row->current_group == $row->last_group) {
                            $bracket->showStart = true;
                        } else {
                            $bracket->showAdvance = true;
                        }
                    }

                    if (is_readable('./images/bracket_' . $bracket->id . '_card.jpg')) {
                        $bracket->cardImage = '/images/bracket_' . $bracket->id . '_card.jpg';
                    } else {
                        $bracket->entrants = Api\Character::getRandomCharacters($bracket, 9);
                    }

                    // All the various button states
                    $bracket->showStartNominations = $bracket->state == BS_NOT_STARTED;
                    $bracket->isFinal = $bracket->state == BS_FINAL;
                    $bracket->showProcessNominees = $bracket->state == BS_NOMINATIONS;
                    $bracket->showBeginEliminations = $bracket->state == BS_NOMINATIONS;
                    $bracket->showEditEntrants = $bracket->state == BS_NOMINATIONS || $bracket->state == BS_ELIMINATIONS || $bracket->state == BS_VOTING;
                    $bracket->showAdvance = isset($bracket->showAdvance) || $bracket->state == BS_VOTING;
                    $bracket->showStats = $bracket->state == BS_VOTING || $bracket->state == BS_FINAL;
                    $bracket->showEdit = $bracket->state != BS_FINAL;
                    $bracket->showDelete = $bracket->showEdit;
                    $bracket->isSiteAdmin = self::$_user->admin;
                    $bracket->csrfToken = self::$_user->csrfToken;
                }

            }

            if ($message) {
                $out->message = $message;
            }

            Lib\Display::renderAndAddKey('content', 'admin/brackets', $out);
        }

        /**
         * Gets a bracket by perma and ensures that the current user has access to it
         */
        protected static function _getBracket($perma) {
            if ($perma) {
                $brackets = Api\Bracket::getUserOwnedBrackets(self::$_user, true, self::$_user->admin);
                $bracket = Api\Bracket::getBracketByPerma($perma, true);

                if ($brackets && $bracket) {
                    // Make sure the user is an owner of the bracket before continuing
                    foreach ($brackets as $userBracket) {
                        if ($bracket->id == $userBracket->id) {
                            return $bracket;
                        }
                    }
                }

            }
            return null;
        }

        /**
         * Returns the list of times for the edit/create forms
         */
        protected static function _generateAdvanceTimes($selectedTime = -1) {
            $retVal = [
                (object)[
                    'label' => 'I want to manage this manually',
                    'value' => -1
                ]
            ];

            $offset = Lib\Url::GetInt('utcOffset', 0, $_COOKIE);
            $offset /= 60;

            for ($i = 0; $i < 24; $i++) {

                // Offset for the user's timezone
                $hour = $i + $offset;
                if ($hour > 23) {
                    $hour -= 24;
                } else if ($hour < 0) {
                    $hour += 24;
                }

                // gross...
                if ($i === 0) {
                    $label = '12am';
                } else if ($i < 12) {
                    $label = $i . 'am';
                } else if ($i === 12) {
                    $label = $i . 'pm';
                } else {
                    $label = ($i - 12) . 'pm';
                }

                $retVal[] = (object)[
                    'label' => $label,
                    'value' => $hour,
                    'selected' => $hour == $selectedTime
                ];
            }

            return $retVal;

        }

        /**
         * Refreshes various generic caches. This is expensive; use sparingly
         */
        protected static function _refreshCaches(Api\Bracket $bracket = null) {
            $cache = Lib\Cache::getInstance();
            $cache->setDisabled(true);

            // Refresh the main collections
            Api\Bracket::getAll();
            Api\Bracket::getUserOwnedBrackets(self::$_user);
            \Controller\Brackets::generate([ 'past' ]);
            \Controller\Brackets::generate([]);

            // Refresh a single bracket if specified
            if ($bracket) {
                Api\Bracket::getById($bracket->id);
                Api\Bracket::getBracketByPerma($bracket->perma);
                Api\Round::getCurrentRounds($bracket->id);
                $bracket->getResults();
            }

            $cache->setDisabled(false);
        }

        /**
         * Creates a message object for the alert banner
         * @param string $type The type of banner, eg "success" or "error"
         * @param string $message The message to display
         * @param boolean $stash Whether to stash this object
         */
        protected static function _createMessage($type, $message, $stash = false) {
            $retVal = new stdClass;
            $retVal->type = $type;
            $retVal->message = $message;

            if ($stash && self::$_user) {
                $cacheKey = self::_stashCacheKey();
                Lib\Cache::getInstance()->set($cacheKey, $retVal);
            }

            return $retVal;
        }

        protected static function _redirectToMain() {
            header('Location: /me/');
            exit;
        }

        /**
         * Retrieves a stashed message from caches and then clears it
         */
        protected static function _getStashedMessage() {
            $retVal = null;
            if (self::$_user) {
                $cache = Lib\Cache::getInstance();
                $cacheKey = self::_stashCacheKey();
                $retVal = $cache->get($cacheKey);
                $cache->set($cacheKey, false);
            }
            return $retVal;
        }

        private static function _stashCacheKey() {
            return self::$_user ? 'Controller::_getStashedMeessage_' . self::$_user->id : null;
        }

        protected static function _generateAges($selectedAge) {
            $selectedAge = (int) $selectedAge;
            return [
                (object)[ 'title' => 'No minimum age requirement', 'value' => 0, 'selected' => $selectedAge === 0  ],
                (object)[ 'title' => '1 month or older', 'value' => 2592000, 'selected' => $selectedAge === 2592000 ],
                (object)[ 'title' => '2 months or older', 'value' => 5184000, 'selected' => $selectedAge === 5184000 ],
                (object)[ 'title' => '3 months or older', 'value' => 7776000, 'selected' => $selectedAge === 7776000 ],
                (object)[ 'title' => '6 months or older', 'value' => 15552000, 'selected' => $selectedAge === 15552000 ],
                (object)[ 'title' => '1 year or older', 'value' => 31104000, 'selected' => $selectedAge === 31104000 ]
            ];
        }

        protected static function authorize() {
            $retVal = self::_verifyCsrf(self::$_user);
            if (!$retVal) {
                $message = self::_createMessage('error', 'There was an error authenticating your account. Please logout and log back in.');
                self::_main($message);
            }
            return $retVal;
        }

    }

}
