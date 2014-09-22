<?php

namespace Controller {
    
    use Lib;
    use Api;

    class JsonApi implements Page {

        public static function render() {

            $retVal = null;
            $action = Lib\Url::Get('action', null);

            switch ($action) {
                case 'brackets':
                    $retVal = Api\Bracket::getAll();
                    break;
                case 'bracket':
                    $retVal = self::_getBracket();
                    break;
                case 'rounds':
                    $retVal = self::_getCurrentRounds();
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
                $bracket = Api\Bracket::getById($bracketId);
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
                $retVal = Api\Round::getCurrentRounds($bracketId);
            }
            return $retVal;
        }

    }

}