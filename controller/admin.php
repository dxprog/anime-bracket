<?php

namespace Controller {

    use Api;
    use Lib;
    use stdClass;

    define('MAX_WIDTH', 600);
    define('MAX_HEIGHT', 500);
    define('BRACKET_IMAGE_SIZE', 150);

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
                    case 'process':
                        if ($state === 'nominations') {
                            $content = self::_displayNominations($bracket);
                        } else if ($state === 'nominee') {
                            self::_processNominee($bracket);
                        } else if ($state === 'characters') {
                            // some other thing which hasn't been done yet
                        }
                        break;
                    case 'upload':
                        self::_uploadFile();
                        break;
                    case 'crop':
                        self::_cropImage();
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

        public static function _displayNominations($bracket, $jsonOnly = false) {
            $retVal = null;

            if ($bracket) {

                $nominee = Api\Nominee::getUnprocessed($bracket->id, 1);

                if (count($nominee) > 0) {
                    $out = new stdClass;
                    $out->bracket = $bracket;
                    $out->nominee = end($nominee);
                    $out->message = isset($message) ? $message : null;
                    $out->similar = $out->nominee->getSimilar($bracket);
                    $characters = Api\Character::getBySimilarName($out->nominee->name, $bracket);
                    $thisBracket = [];
                    $otherBrackets = [];

                    // Split characters up into this bracket and other brackets
                    foreach ($characters as $character) {
                        if ($character->bracketId == $bracket->id) {
                            $thisBracket[] = $character;
                        } else {
                            $otherBrackets[] = $character;
                        }
                    }

                    $out->thisBracketCharacters = count($thisBracket) ? $thisBracket : null;
                    $out->otherBracketCharacters = count($otherBrackets) ? $otherBrackets : null;

                    $retVal = $jsonOnly ? $out : Lib\Display::renderAndAddKey('content', 'admin/nominee', $out);
                }
            }

            return $retVal;

        }

        private static function _processNominee($bracket) {

            $out = new stdClass;
            $out->success = false;

            if ($bracket) {

                $name = Lib\Url::Post('name');
                $source = Lib\Url::Post('source');
                $imageFile = Lib\Url::Post('imageFile');
                $nomineeId = Lib\Url::Post('id', true);
                $ignore = Lib\Url::Post('ignore') === 'true';
                $nominees = Lib\Url::Post('nominee');
                $nominees = $nominees ?: [];

                if ((($name && $source && $imageFile) || $ignore) && $nomineeId) {

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
                                $out = self::_displayNominations($bracket, true);
                                $out->success = true;
                                $out->message = '"' . $character->name . '" successfully processed';
                            } else {
                                $out->message = 'Unable to save character to database';
                            }

                        } else {
                            $out->message = 'Image must by JPEG, GIF, or PNG and 150x150 pixels';
                        }

                    } else {
                        $out->success = true;
                        $out->message = 'Nominee' . (count($nominees) > 0 ? 's' : '') . ' deleted';
                    }

                    if ($out->success) {
                        $nominees[] = $nomineeId;
                        Api\Nominee::markAsProcessed($nominees);
                    }

                }

            } else {
                $out->message = 'Invalid bracket';
            }

            Lib\Display::renderJson($out);

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

        private static function _uploadFile() {

            $out = new stdClass;
            $out->success = false;
            $out->message = 'Unable to upload image';

            $fileName = './cache/' . uniqid();
            $tmpFile = $_FILES['upload']['tmp_name'];
            if (is_uploaded_file($tmpFile) && move_uploaded_file($tmpFile, $fileName)) {
                $type = Lib\ImageLoader::getImageType($fileName);
                if ($type) {
                    rename($fileName, $fileName . '.' . $type);
                    $out->success = true;
                    $out->fileName = str_replace('.', '', $fileName) . '.' . $type;
                } else {
                    unlink($fileName);
                    $out->message = 'Invalid image';
                }
            }

            Lib\Display::renderJson($out);

        }

        private static function _cropImage() {

            $out = new stdClass;
            $out->success = false;
            $out->message = 'Unable to crop image';

            $imageFile = Lib\Url::Post('imageFile');
            $x = Lib\Url::Post('x', true);
            $y = Lib\Url::Post('y', true);
            $width = Lib\Url::Post('width', true);
            $height = Lib\Url::Post('height', true);

            if ($imageFile && null !== $x && null !== $y && null !== $width && null !== $height) {
                $imageFile = $imageFile{0} === '/' ? '.' . $imageFile : $imageFile;
                $image = Lib\ImageLoader::loadImage($imageFile);
                if ($image) {
                    $image = self::_sizeUp($image->image);
                    $croppedImage = imagecreatetruecolor(BRACKET_IMAGE_SIZE, BRACKET_IMAGE_SIZE);
                    imagecopyresampled($croppedImage, $image, 0, 0, $x, $y, BRACKET_IMAGE_SIZE, BRACKET_IMAGE_SIZE, $width, $height);
                    $fileName = '/cache/' . md5($imageFile) . '.jpg';
                    imagejpeg($croppedImage, '.' . $fileName);
                    imagedestroy($image);
                    imagedestroy($croppedImage);
                    $out->success = true;
                    $out->fileName = $fileName;
                }
            } else {
                $out->message = 'Parameters missing';
            }

            Lib\Display::renderJson($out);

        }

        private static function _sizeUp($image) {
            $width = imagesx($image);
            $height = imagesy($image);
            if ($width > MAX_WIDTH || $height > MAX_HEIGHT) {
                $newHeight = 0;
                $newWidth = 0;
                if ($width > $height) {
                    $ratio = $height / $width;
                    $newWidth = MAX_WIDTH;
                    $newHeight = $newWidth * $ratio;
                } else {
                    $ratio = $width / $height;
                    $newHeight = MAX_HEIGHT;
                    $newWidth = $newHeight * $ratio;
                }

                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $newImage;
            }
            return $image;
        }

        private static function _verifyImage($url) {
            $image = Lib\ImageLoader::loadImage($url);
            $retVal = $image && imagesx($image->image) === BRACKET_IMAGE_SIZE && imagesy($image->image) === BRACKET_IMAGE_SIZE;
            imagedestroy($image->image);
            return $retVal;
        }

    }

}