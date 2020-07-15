<?php

namespace Controller\Admin {

    use Api;
    use Lib;
    use stdClass;

    class Start extends \Controller\Me {

        public static function generate(array $params) {
            $bracket = self::_getBracket(array_shift($params));
            if ($bracket) {
                self::_setState($bracket, array_shift($params));
            }
        }

        public static function _setState(Api\Bracket $bracket, $state) {

            $message = self::_createMessage('error', 'There was an error setting the bracket state.');
            $setState = false;
            $stateMap = [
                'nominations' => BS_NOMINATIONS,
                'eliminations' => BS_ELIMINATIONS,
                'voting' => BS_VOTING
            ];

            if ($bracket && isset($stateMap[$state])) {

                $stateId = $stateMap[$state];

                if ($stateId == BS_NOMINATIONS && $bracket->state == BS_NOT_STARTED) {
                    $setState = true;
                } else if ($stateId == BS_ELIMINATIONS && $bracket->state == BS_NOMINATIONS) {
                    $setState = true;
                    return self::_beginEliminations($bracket);
                } else if ($stateId == BS_VOTING && $bracket->state == BS_ELIMINATIONS) {
                    $setState = true;
                    return self::_generateBracket($bracket);
                }

                if ($setState) {
                    $bracket->state = $stateId;
                    if ($bracket->sync()) {
                        $message = self::_createMessage('success', '"' . $bracket->name . '" has advanced to the ' . $state . ' phase.', true);
                        self::_refreshCaches($bracket);
                        self::_redirectToMain();
                    }
                }

            }

            return self::_main($message, true);

        }

        public static function _beginEliminations(Api\Bracket $bracket) {
            $days = Lib\Url::Post('days', true);

            if ($bracket && $bracket->state == BS_NOMINATIONS) {
                if (!$days) {
                    $result = Lib\Db::Query('SELECT COUNT(1) AS total FROM `character` WHERE bracket_id = :id', [ ':id' => $bracket->id ]);
                    if ($result) {
                        $count = Lib\Db::Fetch($result);
                        $bracket->count = (int) $count->total;
                    }
                    Lib\Display::renderAndAddKey('content', 'admin/eliminations', $bracket);
                } else {
                    $days = (int) $days;
                    $result = Lib\Db::Query('SELECT character_id FROM `character` WHERE bracket_id = :id ORDER BY RAND()', [ ':id' => $bracket->id ]);
                    if ($result && $result->count) {
                        $group = 0;
                        $order = 0;
                        while ($row = Lib\Db::Fetch($result)) {
                            $round = new Api\Round();
                            $round->bracketId = $bracket->id;
                            $round->tier = 0;
                            $round->group = $group;
                            $round->order = $order;
                            $round->character1Id = $row->character_id;
                            $round->character2Id = 1;
                            $round->final = 0;
                            $round->deleted = 0;
                            $round->sync();
                            $order++;
                            $group = $order % $days;
                        }

                        $bracket->state = BS_ELIMINATIONS;
                        if ($bracket->sync()) {
                            $message = self::_createMessage('success', 'Eliminations for "' . $bracket->name . '" have started.', true);
                        }

                        self::_refreshCaches($bracket);
                        self::_redirectToMain();

                    }
                }
            }
        }

        public static function _generateBracket(Api\Bracket $bracket) {
            $retVal = null;
            if ($bracket) {

                $availableEntrants = Api\Round::getRoundCountForTier($bracket, 0);

                // Can't have much of a bracket with only two entrants...
                if ($availableEntrants < 2) {
                    $message = self::_createMessage('error', 'There are not enough entrants to generate a bracket :(');
                    self::_main($message);
                } else {
                    if (count($_POST) > 0) {
                        $entrants = Lib\Url::Post('entrants', true);
                        $groups = Lib\Url::Post('groups', true);

                        if ($entrants && $groups) {

                            // Verify that the entrants/groups combo doesn't exceed to number of available entrants
                            if ($entrants * $groups > $availableEntrants) {
                                $message = self::_createMessage('error', 'Cannot generate a bracket of that size');
                                self::_main($message);
                            } else {
                                $bracket->advance();
                                if ($bracket->createBracketFromEliminations($entrants * $groups, $groups)) {
                                    $message = self::_createMessage('success', 'Voting for bracket "' . $bracket->name . '" has successfully started!');
                                    self::_refreshCaches($bracket);
                                    self::_redirectToMain();
                                } else {
                                    $message = self::_createMessage('error', 'There are not enough entrants to create a bracket of that size');
                                    self::_redirectToMain();
                                }
                            }

                        } else {
                            $message = self::_createMessage('error', 'There was an error starting the bracket');
                            self::_redirectToMain();
                        }
                    } else {
                        $out = (object)[
                            'bracket' => $bracket,
                            'count' => $availableEntrants
                        ];
                        Lib\Display::renderAndAddKey('content', 'admin/start_bracket', $out);
                    }
                }
            }
        }

    }

}
