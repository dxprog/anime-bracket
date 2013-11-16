<?php

namespace Controller {
    
    use Api;
    use Lib;

    class Stats implements Page {

        public static function render() {
            $out = null;
            $action = Lib\Url::Get('action', 'votes');
            $bracketId = Lib\Url::GetInt('bracketId', null);
            
            switch ($action) {
                case 'character':
                    $out = self::_getCharacterStats(Lib\Url::GetInt('id'));
                    break;
                case 'userVotes':
                    if ($bracketId) {
                        $out = Api\Stats::getUsersVotingOverTime([ 'bracketId' => $bracketId ]);
                    }
                    break;
                case 'votes':
                default:
                    if ($bracketId) {
                        $out = Api\Stats::getVotesOverTime([ 'bracketId' => $bracketId, 'granularity' ]);
                    }
                    break;
            }

            header('Content-Type: text/javascript');
            $out = json_encode($out);
            $callback = Lib\Url::Get('callback');
            $out = $callback ? $callback . '(' . $out . ');' : $out;
            echo $out;
            exit;

        }

        private static function _getCharacterStats($id) {
            $retVal = null;
            if ($id) {
                $db = Lib\Mongo::getDatabase();
                $retVal = $db->characterRankingInfo->findOne([ 'characterId' => $id ]);
                unset($retVal['_id']);
            }
            return $retVal;
        }

    }

}