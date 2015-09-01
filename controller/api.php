<?php

namespace Controller {

    use Lib;

    class Api extends Page {

        public static function generate(array $params) {

            $retVal = null;
            $action = array_shift($params);

            switch ($action) {
                case 'brackets':
                    $retVal = \Api\Bracket::getAll();
                    break;
                case 'bracket':
                    $retVal = self::_getBracket();
                    break;
                case 'results':
                    $retVal = self::_getResults();
                    break;
                case 'rounds':
                    $retVal = self::_getCurrentRounds();
                    break;
                case 'login':
                    header('Location: ' . str_replace('authorize', 'authorize.compact', \Api\User::getLoginUrl('/')));
                    exit;
                case 'user':
                    $retVal = \Api\User::getCurrentUser();
                    break;
                case 'characters':
                    $retVal = self::_getBracketCharacters();
                    break;
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($retVal);
            exit;

        }

        private static function _getBracket() {
            $retVal = null;
            $bracketId = Lib\Url::GetInt('bracketId', null);
            if ($bracketId) {
                $retVal = \Api\Bracket::getById($bracketId);
            }
            return $retVal;
        }

        private static function _getResults() {
            $retVal = null;
            $bracketId = Lib\Url::GetInt('bracketId', null);
            if ($bracketId) {
                $bracket = \Api\Bracket::getById($bracketId);
                if ($bracket) {
                    $retVal = $bracket->getResults();
                }
            }
            return $retVal;
        }

        private static function _getCurrentRounds() {
            $retVal = null;
            $bracketId = Lib\Url::GetInt('bracketId', null);
            if ($bracketId) {
                $retVal = \Api\Round::getCurrentRounds($bracketId);
            }
            return $retVal;
        }

        private static function _getBracketCharacters() {
            $retVal = null;
            $bracketId = Lib\Url::GetInt('bracketId', null);
            $count = Lib\Url::GetInt('count', null);
            if ($bracketId) {
                //If $count has a value, get random characters from the given bracket
                if ($count) {
                    $bracket = \Api\Bracket::getById($bracketId);
                    if($bracket) { //3 levels of IFs. This is getting rediculous
                        $retVal = \Api\Character::getRandomCharacters($bracket, $count);
                    }
                } else {
                   $retVal = \Api\Character::getByBracketId($bracketId);
                }
            }
            return $retVal;
        }

    }

}
