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
                    $retVal = self::_getBracket($params);
                    break;
                case 'results':
                    $retVal = self::_getResults($params);
                    break;
                case 'rounds':
                    $retVal = self::_getCurrentRounds($params);
                    break;
                case 'login':
                    header('Location: ' . str_replace('authorize', 'authorize.compact', \Api\User::getLoginUrl('/')));
                    exit;
                case 'user':
                    $retVal = \Api\User::getCurrentUser();
                    break;
                case 'characters':
                    $retVal = self::_getBracketCharacters($params);
                    break;
            }

            // CORS support
            if (isset($_SERVER['HTTP_ORIGIN'])) {
                header('Access-Control-Allow-Origin: *');
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($retVal);
            exit;

        }

        /**
         * Returns info for a bracket
         *
         * Endpoint URL: /api/bracket/?bracketId=XXX OR /api/bracket/bracket-perma-slug
         */
        private static function _getBracket(array $params) {
            $retVal = null;
            $bracketId = self::_getBracketId($params);
            if ($bracketId) {
                $retVal = \Api\Bracket::getById($bracketId);
            }
            return $retVal;
        }

        /**
         * Returns results for a bracket
         *
         * Endpoint URL: /api/results/?bracketId=XXX OR /api/results/bracket-perma-slug
         */
        private static function _getResults(array $params) {
            $retVal = null;
            $bracketId = self::_getBracketId($params);
            if ($bracketId) {
                $bracket = \Api\Bracket::getById($bracketId);
                if ($bracket) {
                    $retVal = $bracket->getResults();
                }
            }
            return $retVal;
        }

        /**
         * Returns all current rounds in a bracket
         *
         * Endpoint URL: /api/rounds/?bracketId=XXX OR /api/rounds/bracket-perma-slug
         */
        private static function _getCurrentRounds(array $params) {
            $retVal = null;
            $bracketId = self::_getBracketId($params);
            if ($bracketId) {
                $retVal = \Api\Round::getCurrentRounds($bracketId);
            }
            return $retVal;
        }

        /**
         * Returns all characters or "count" number of random characters from a bracket
         *
         * Endpoint URL: /api/characters/?bracketId=XXX&count=XXX
         * Endpoint URL: /api/characters/bracket-perma-slug?count=XXX
         */
        private static function _getBracketCharacters(array $params) {
            $retVal = null;
            $bracketId = self::_getBracketId($params);
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

        /**
         * Attempts to get a request's bracket ID either by bracket perma or
         * the bracketId query string parameter
         */
        private static function _getBracketId(array $params) {
            $bracketId = Lib\Url::GetInt('bracketId', null);

            if (!$bracketId) {
                $perma = count($params) > 0 ? $params[0] : null;
                if ($perma) {
                    $bracket = \Api\Bracket::getBracketByPerma($perma);
                    if ($bracket) {
                        $bracketId = $bracket->id;
                    }
                }
            }

            return $bracketId;
        }

    }

}
