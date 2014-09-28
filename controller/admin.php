<?php

namespace Controller {

    use Api;
    use Lib;
    use stdClass;

    class Admin implements Page {

        private static $_user = null;

        public static function render() {

            $content = null;

            self::$_user = Api\User::getCurrentUser();

            Lib\Display::setLayout('admin');

            // Hork of the hard work done in the brackets controller
            Bracket::initTemplateHelpers();

            if (!self::$_user) {
                header('Location: /login/?redirect=' . urlencode('/admin/'));
            } else {
                
                $action = Lib\Url::Get('action', null);
                $perma = Lib\Url::Get('perma', null);
                $state = Lib\Url::Get('state', null);
                $bracket = self::_getBracket($perma);

                switch ($action) {
                    case 'create':
                        self::_createBracket();
                        break;
                    case 'start':
                        self::_setState($bracket, $state);
                        break;
                    case 'eliminations':
                        self::_beginEliminations($bracket);
                        break;
                    case 'advance':
                        self::_advanceBracket($bracket);
                        break;
                    case 'createBracket':
                        self::_generateBracket($bracket);
                        break;
                    default:
                        self::_main();
                        break;
                }

            }

            return $content;

        }

        public static function _createBracket() {

            // Create the bracket on POST
            if ($_POST) {

                $name = Lib\Url::Post('bracketName', null);
                $rules = Lib\Url::Post('rules', null);

                if ($name && $rules) {
                    $bracket = new Api\Bracket();
                    $bracket->name = trim($name);
                    $bracket->rules = $rules;
                    $bracket->state = 0;
                    $bracket->start = time();
                    $bracket->generatePerma();

                    if ($bracket->sync()) {
                        header('Location: /admin/?flushCache');
                        exit;
                    }

                }

            }

            // Or display the form
            Lib\Display::renderAndAddKey('content', 'admin/bracket', $_POST);

        }

        public static function _main($message = null) {
            $out = new stdClass;
            $out->brackets = Api\Bracket::getUserOwnedBrackets(self::$_user);

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

            if ($message) {
                $out->message = $message;
            }

            Lib\Display::renderAndAddKey('content', 'admin/brackets', $out);
        }

        public static function _generateBracket(Api\Bracket $bracket) {
            $retVal = null;
            if ($bracket) {
                if (count($_POST) > 0) {
                    $entrants = Lib\Url::Post('entrants', true);
                    $groups = Lib\Url::Post('groups', true);
                    if ($entrants && $groups) {
                        $bracket->createBracketFromEliminations($entrants, $groups);
                        $retVal = self::_main();
                    }
                } else {
                    $count = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(1) AS total FROM round WHERE round_tier = 0 AND bracket_id = :bracketId', [ ':bracketId' => $bracket->id ]));
                    $i = 2;
                    $entrants = [];
                    $count = (int) $count->total;
                    while ($i < $count) {
                        $entrants[] = $i;
                        $i *= 2;
                    }
                    $retVal = Lib\Display::compile($entrants, 'admin/create_bracket');
                }
            }
            return $retVal;
        }

        public static function _advanceBracket(Api\Bracket $bracket) {
            if ($bracket) {
                $bracket->advance();
            }
            return self::_main();
        }

        public static function _beginEliminations(Api\Bracket $bracket) {
            $days = Lib\Url::GetInt('days', null);
            $retVal = null;
            if ($bracket && $bracket->state == BS_NOMINATIONS) {
                if (!$days) {
                    $result = Lib\Db::Query('SELECT COUNT(1) AS total FROM `character` WHERE bracket_id = :id', [ ':id' => $bracket->id ]);
                    if ($result) {
                        $count = Lib\Db::Fetch($result);
                        $bracket->count = (int) $count->total;
                    }
                    $retVal = Lib\Display::compile($bracket, 'admin/eliminations');
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
                            $round->sync();
                            $order++;
                            $group = $order % $days;
                        }
                        self::_setState($bracket->id, BS_ELIMINATIONS);
                        $retVal = self::_main();
                    }
                }
            }
            return $retVal;
        }

        public static function _setState($bracket, $state) {
            
            $message = self::_createMessage('error', 'There was an error setting the bracket state.');

            if ($bracket) {
                $stateMap = [
                    'nominations' => BS_NOMINATIONS,
                    'eliminations' => BS_ELIMINATIONS,
                    'voting' => BS_VOTING
                ];

                if ($bracket && isset($stateMap[$state])) {
                    $bracket->state = $stateMap[$state];
                    if ($bracket->sync()) {
                        $message = self::_createMessage('success', '"' . $bracket->name . '" has advanced to the ' . $state . ' phase.');
                    }
                }
            }

            return self::_main($message);

        }

        public static function _displayNominations() {
            $retVal = null;
            $bracketId = Lib\Url::GetInt('bracket');

            if ($bracketId) {
                if (count($_POST) > 0) {
                    $char = new Api\Character();
                    $id = Lib\Url::Post('id', true);
                    if ($id) {
                        $create = false;
                        $action = Lib\Url::Post('form_action');
                        if ($action === 'create') {
                            $char->name = Lib\Url::Post('name');
                            $char->source = Lib\Url::Post('source');
                            $char->bracketId = $bracketId;
                            $fail = true;
                            if ($char->name && $char->source && is_uploaded_file($_FILES['headshot']['tmp_name'])) {
                                if ($char->sync()) {
                                    $fileName = IMAGE_LOCATION . '/' . base_convert($char->id, 10, 36) . '.jpg';
                                    if (move_uploaded_file($_FILES['headshot']['tmp_name'], $fileName)) {
                                        $create = true;
                                    } else {
                                        $message = 'Picture failed to upload';
                                        $char->delete();
                                    }
                                } else {
                                    $message = 'Unable to create the character entry';
                                }

                                if ($fail) {
                                    $nominee = Api\Nominee::getById($id);
                                }
                            } else {
                                $message = 'Field missing';
                            }
                        }

                        if (strpos($action, 'clone') === 0) {
                            $pieces = explode('|', $action);
                            $characterId = end($pieces);
                            if (is_numeric($characterId)) {
                                $character = Api\Character::getById($characterId);
                                if ($character) {
                                    $character->id = 0;
                                    $character->bracketId = $bracketId;
                                    if ($character->sync()) {
                                        copy(IMAGE_LOCATION . '/' . base_convert($characterId, 10, 36) . '.jpg', IMAGE_LOCATION . '/' . base_convert($character->id, 10, 36) . '.jpg');
                                        $create = true;
                                    }
                                }
                            }
                        }

                        if ($create || $action === 'skip') {
                            $params = [ ':id0' => $id ];
                            $similar = Lib\Url::Post('chkProcess');
                            if (count($similar) > 0) {
                                for ($i = 0, $count = count($similar); $i < $count; $i++) {
                                    $params[':id' . ($i + 1)] = $similar[$i];
                                }
                            }
                            if (Lib\Db::Query('UPDATE nominee SET nominee_processed = 1 WHERE nominee_id IN (' . implode(',', array_keys($params)) . ')', $params)) {
                                $message = $create ? 'Character created successfully!' : 'Nominees have been marked as processed';
                                $fail = false;
                            } else {
                                $message = 'Unable to finalize nominee processing';
                                if (isset($char)) {
                                    $char->delete();
                                    unlink($fileName);
                                }
                            }
                        }

                    }
                }

                $bracket = Api\Bracket::getById($bracketId);
                $nominee = Api\Nominee::getUnprocessed($bracket->id, 1);

                if (count($nominee) > 0) {
                    $out = new stdClass;
                    $out->bracket = $bracket;
                    $out->nominee = end($nominee);
                    $out->message = isset($message) ? $message : null;
                    $out->similar = $out->nominee->getSimilar($bracket);
                    $out->character = Api\Character::getBySimilarName($out->nominee->name, $bracket);
                    $retVal = Lib\Display::compile($out, 'admin/nominee');
                }
            }

            return $retVal;

        }

        private static function _getBracket($perma) {
            if ($perma) {
                $brackets = Api\Bracket::getUserOwnedBrackets(self::$_user);
                $bracket = Api\Bracket::getBracketByPerma($perma);

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

        private static function _createMessage($type, $message) {
            $retVal = new stdClass;
            $retVal->type = $type;
            $retVal->message = $message;
            return $retVal;
        }

    }

}