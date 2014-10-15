<?php

namespace Api {
    
    use Lib;

    class Votes {

        /**
         * Takes a list of rounds and returns rounds with open voting
         * that the user has not yet voted on
         */
        public static function getOpenRounds($user, $votes) {
            
            $params = [ ':userId' => $user->id ];
            for ($i = 0, $count = count($votes); $i < $count; $i++) {
                $params[':round' . $i] = $votes[$i]->roundId;
            }
            
            $roundKeys = implode(',', array_keys($params));
            $query = 'SELECT round_id FROM votes WHERE user_id = :userId AND round_id IN (' . $roundKeys . ') UNION ';
            $query .= 'SELECT round_id FROM round WHERE round_id IN (' . $roundKeys . ') AND round_final = 1';

            $result = Lib\Db::Query($query, $params);
            $retVal = [];
            if ($result && $result->count > 0) {
                while ($row = Lib\Db::Fetch($result)) {
                    $retVal[$row->round_id] = true;
                }
            }
            
            return $retVal;
        }

    }

}