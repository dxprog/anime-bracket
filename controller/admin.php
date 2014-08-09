<?php

namespace Controller {

    use Api;
    use Lib;
    use stdClass;

    class Admin implements Page {

        public static function render() {

            $content = null;

            $user = Api\User::getCurrentUser();
            if (!$user || !$user->admin) {
                header('Location: /login/?redirect=' . urlencode('/admin/'));
            } else {
                $action = Lib\Url::Get('action', null);
                switch ($action) {
                    case 'nominations':
                        $content = self::_displayNominations();
                        break;
                    case 'eliminations':
                        $content = self::_beginEliminations();
                        break;
                    case 'advance':
                        $content = self::_advanceBracket();
                        break;
                    case 'createBracket':
                        $content = self::_createBracket();
                        break;
                    case 'setState':
                        $id = Lib\Url::GetInt('bracket', null);
                        $state = Lib\Url::GetInt('state', null);
                        self::_setState($id, $state);
                    default:
                        $content = self::_main();
                        break;
                }

            }

            Lib\Display::setTemplate('admin');
            Lib\Display::setVariable('content', $content);

            return $content;

        }

        public static function registerExtension($class, $method, $type) { }

        public static function _main() {
            $brackets = Api\Bracket::getAll(true);
            return Lib\Display::compile($brackets, 'admin/brackets_overview');
        }

        public static function _createBracket() {
            $retVal = null;
            $bracket = self::_getBracket();
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

        public static function _advanceBracket() {
            $bracket = self::_getBracket();
            if ($bracket) {
                $bracket->advance();
            }
            return self::_main();
        }

        public static function _beginEliminations() {
            $days = Lib\Url::GetInt('days', null);
            $bracket = self::_getBracket();
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

        public static function _setState($id, $state) {
            if ($id && $state) {
                $bracket = Api\Bracket::getById($id);
                if ($bracket) {
                    $bracket->state = $state;
                    $bracket->sync();
                }
            }
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

        private static function _getBracket() {
            $id = Lib\Url::GetInt('bracket', 0);
            return Api\Bracket::getById($id);
        }

    }

}