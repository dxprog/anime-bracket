<?php

namespace Controller\Admin {

    use Api;
    use Lib;
    use stdClass;

    class Process extends \Controller\Me {

        public static function generate(array $params) {

            $bracket = self::_getBracket(array_shift($params));
            if ($bracket) {
                $state = array_shift($params);
                switch ($state) {
                    case 'nominations':
                        self::_displayNominations($bracket);
                        break;
                    case 'nominee':
                        self::_processNominee($bracket);
                        break;
                    case 'auto_process':
                        self::_autoProcessNominees($bracket);
                        break;
                    case 'characters':
                        self::_displayCharacters($bracket);
                        break;
                    case 'character':
                        self::_updateCharacter($bracket);
                        break;
                }
            }

        }

        public static function _displayNominations(Api\Bracket $bracket, $jsonOnly = false, $message = null) {
            $retVal = null;

            Lib\Cache::setDisabled(true);
            $nominee = Api\Nominee::getUnprocessed($bracket->id, 1);
            Lib\Cache::setDisabled(false);

            if (count($nominee) > 0) {
                $out = new stdClass;
                $out->bracket = $bracket;
                $out->nominee = end($nominee);
                $out->message = isset($message) ? $message : null;
                $out->similar = $out->nominee->getSimilar($bracket);
                $out->stats = Api\Nominee::getUnprocessedCount($bracket);
                $characters = Api\Character::getBySimilarName($out->nominee->name, $bracket);

                // Split characters up into this bracket and other brackets
                if ($characters && count($characters)) {
                    $thisBracket = [];
                    $otherBrackets = [];
                    foreach ($characters as $character) {
                        if ($character->bracketId == $bracket->id) {
                            $thisBracket[] = $character;
                        } else {
                            $otherBrackets[] = $character;
                        }
                    }

                    $out->thisBracketCharacters = count($thisBracket) ? $thisBracket : null;
                    $out->otherBracketCharacters = count($otherBrackets) ? $otherBrackets : null;
                }

                $out->hasSimilar = (isset($out->thisBracketCharacters) && null !== $out->thisBracketCharacters) || (isset($out->otherBracketCharacters) && null !== $out->otherBracketCharacters) || null !== $out->similar;

                $retVal = $jsonOnly ? $out : Lib\Display::renderAndAddKey('content', 'admin/nominee', $out);
            } else {
                $retVal = (object)[
                    'bracket' => $bracket,
                    'stats' => [
                        'total' => 0,
                        'uniques' => 0
                    ]
                ];

                if (!$jsonOnly) {
                    $retVal = Lib\Display::renderAndAddKey('content', 'admin/nominee', $retVal);
                }
            }

            return $retVal;

        }

        private static function _processNominee(Api\Bracket $bracket) {

            $out = new stdClass;
            $out->success = false;

            $name = Lib\Url::Post('name');
            $source = Lib\Url::Post('source');
            $imageFile = Lib\Url::Post('imageFile');
            $nomineeId = Lib\Url::Post('id', true);
            $ignore = Lib\Url::Post('ignore') === 'true';
            $nominees = Lib\Url::Post('nominee');
            $nominees = $nominees ?: [];

            if ((($name && $imageFile) || $ignore) && $nomineeId) {

                if (!$ignore) {

                    $imageFile = $imageFile{0} === '/' ? '.' . $imageFile : $imageFile;

                    // Verify the image is an image and the correct size
                    if (self::_verifyImage($imageFile)) {

                        $character = new Api\Character();
                        $character->name = $name;
                        $character->bracketId = $bracket->id;
                        $character->source = $source;


                        if ($character->sync()) {
                            // Save the character image off in the correct directory and as a JPEG
                            $image = Lib\ImageLoader::loadImage($imageFile);
                            imagejpeg($image->image, IMAGE_LOCATION . '/' . base_convert($character->id, 10, 36) . '.jpg');
                            imagedestroy($image->image);
                            $out->success = true;
                            $out->message = '"' . $character->name . '" successfully processed';
                        } else {
                            $out->message = 'Unable to save character to database';
                        }

                    } else {
                        $out->message = 'Image must by JPEG, GIF, or PNG and 150x150 pixels';
                    }

                } else {
                    $out = self::_displayNominations($bracket, true);
                    $out->success = true;
                    $out->message = 'Nominee' . (count($nominees) > 0 ? 's' : '') . ' deleted';
                }

                if ($out->success) {
                    $nominees[] = $nomineeId;
                    Api\Nominee::markAsProcessed($nominees);
                    $nominee = self::_displayNominations($bracket, true);
                    $nominee->success = $out->success;
                    $nominee->message = $out->message;
                    $out = $nominee;
                }

            } else {
                $out->message = 'Some fields were not filled out correctly';
            }

            Lib\Display::renderJson($out);

        }

        private static function _autoProcessNominees(Api\Bracket $bracket) {
            
            // Get all characters and nominees in this bracket
            $characters = Api\Character::queryReturnAll([ 'bracketId' => $bracket->id ]);
            $nominees = Api\Nominee::queryReturnAll([ 'bracketId' => $bracket->id ]);
            $count = 0;

            if ($characters && $nominees) {
                
                // Hash maps for fast tracking of nominees/characters entered
                // The hash id is Name + Source
                $verifiedHash = [];
                $nomineesHash = [];

                foreach ($characters as $character) {
                    $key = self::_normalizeString($character->name) . '_' . self::_normalizeString($character->source);
                    $verifiedHash[$key] = true;
                }

                foreach ($nominees as $nominee) {

                    $key = self::_normalizeString($nominee->name) . '_' . self::_normalizeString($nominee->source);

                    // If this nominee is marked as processed, add it to the verified hash
                    if ($nominee->processed) {
                        $verifiedHash[$key] = true;
                    } else {

                        // First, check to see if this has a verified counterpart
                        if (isset($verifiedHash[$key])) {
                            if (!isset($nomineesHash[$key])) {
                                $nomineesHash[$key] = [ $nominee->id ];
                                $count++;
                            }
                        } else {

                            // See if there's another nominee similar to this one.
                            // If so, add this nominee to the IDs to mark as processed.
                            // Otheriwse, note that this name has turned up, but we want to leave this one unprocessed
                            if (isset($nomineesHash[$key])) {
                                $nomineesHash[$key][] = $nominee->id;
                                $count++;
                            } else {
                                $nomineesHash[$key] = [];
                            }

                        }

                    }

                }

                if (count($nomineesHash)) {

                    // Merge all the ID arrays down to one array of IDs
                    $nomineeIds = [];
                    foreach ($nomineesHash as $key => $ids) {
                        $nomineeIds = array_merge($nomineeIds, $ids);
                    }

                    // Mark as processed
                    Api\Nominee::markAsProcessed($nomineeIds);

                }

            }

            self::_displayNominations($bracket, false, $count . ' duplicate nominee' . ($count !== 1 ? 's' : '') . ' were processed');

        }

        private static function _normalizeString($string) {
            $string = preg_replace('/[^A-Za-z0-9\s]/', ' ', $string);
            $newString = str_replace('  ', ' ', $string);
            while ($newString !== $string) {
                $string = $newString;
                $newString = str_replace('  ', ' ', $string);
            }
            return strtolower($string);
        }

        private static function _displayCharacters(Api\Bracket $bracket) {
            $out = new stdClass;
            Lib\Cache::setDisabled(true);
            $out->characters = Api\Character::getByBracketId($bracket->id);
            $out->bracket = $bracket;
            Lib\Display::renderAndAddKey('content', 'admin/characters', $out);
            Lib\Cache::setDisabled(false);
        }

        private static function _updateCharacter(Api\Bracket $bracket) {
            $out = new stdClass;
            $out->success = false;

            $id = Lib\Url::Post('characterId', true);
            $name = Lib\Url::Post('name');
            $source = Lib\Url::Post('source');
            $action = Lib\Url::Post('action');
            if ($id && $name && $action) {
                $out->action = $action;
                $character = Api\Character::getById($id);
                if ($character && $character->bracketId == $bracket->id) {
                    if ($action == 'update') {
                        $character->name = $name;
                        $character->source = $source;
                        if ($character->sync()) {
                            $out->success = true;
                        } else {
                            $out->message = 'Error updating database';
                        }
                    } else if ($action == 'delete') {
                        if ($bracket->state == BS_NOMINATIONS || $bracket->state == BS_ELIMINATIONS) {
                            if ($character->delete()) {
                                $out->success = true;
                            } else {
                                $out->message = 'Delete failed';
                            }
                        } else {
                            $out->message = 'Cannot delete characters after voting has started';
                        }
                    } else {
                        $out->message = 'Unknown action';
                    }
                } else {
                    $out->message = 'Character does not belong to this bracket';
                }
            } else {
                $out->message = 'Missing fields';
            }

            Lib\Display::renderJson($out);
        }

        private static function _verifyImage($url) {
            $image = Lib\ImageLoader::loadImage($url);
            $retVal = $image && imagesx($image->image) === BRACKET_IMAGE_SIZE && imagesy($image->image) === BRACKET_IMAGE_SIZE;
            imagedestroy($image->image);
            return $retVal;
        }

    }

}
