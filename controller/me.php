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
            $out->brackets = Api\Bracket::getUserOwnedBrackets(self::$_user, $force);

            if ($out->brackets) {

                // Check for card images
                foreach ($out->brackets as $bracket) {
                    if (is_readable('./images/bracket_' . $bracket->id . '_card.jpg')) {
                        $bracket->cardImage = '/images/bracket_' . $bracket->id . '_card.jpg';
                    } else {
                        $bracket->entrants = Api\Character::getRandomCharacters($bracket, 9);
                    }
                }

                // Sort the brackets by reverse date
                usort($out->brackets, function($a, $b) {
                    return $a->start > $b->start ? -1 : 1;
                });

            }

            // Decorate each bracket with some information about what phase it can
            // safely move to. Mostyl this is for eliminations
            foreach ($out->brackets as $bracket) {
                if ($bracket->state == BS_ELIMINATIONS) {
                    // Should query all the brackets at once, but I'm feeling lazy tonight...
                    $result = Lib\Db::Query('SELECT MIN(round_group) AS current_group, MAX(round_group) AS last_group FROM `round` WHERE bracket_id = :bracketId AND round_final = 0', [ ':bracketId' => $bracket->id ]);
                    if ($result && $result->count) {
                        $row = Lib\Db::Fetch($result);

                        // If the eliminations are on the last group, don't show the
                        // advance button
                        if ($row->current_group == $row->last_group) {
                            $bracket->showStart = true;
                        } else {
                            $bracket->showAdvance = true;
                        }
                    }
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
                $brackets = Api\Bracket::getUserOwnedBrackets(self::$_user);
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
            Lib\Cache::setDisabled(true);

            // Refresh the main collections
            Api\Bracket::getAll();
            Api\Bracket::getUserOwnedBrackets(self::$_user);
            \Controller\Brackets::generate([ 'past' ]);
            \Controller\Brackets::generate([]);

            // Refresh a single bracket if specified
            if ($bracket) {
                Api\Bracket::getBracketByPerma($bracket->perma);
                Api\Round::getCurrentRounds($bracket->id);
                $bracket->getResults();
            }

            Lib\Cache::setDisabled(false);
        }

        protected static function _createMessage($type, $message) {
            $retVal = new stdClass;
            $retVal->type = $type;
            $retVal->message = $message;
            return $retVal;
        }

    }

}