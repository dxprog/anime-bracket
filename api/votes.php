<?php

namespace Api {
    
    class Votes {

        /**
         * Takes a list of rounds and returns rounds with open voting
         * that the user has not yet voted on
         */
        public static function getOpenRounds($user, $votes) {
            
            $roundKeys = [];
            $params = [ ':userId' => $user->id ];
            for ($i = 0, $count = count($votes); $i < $count; $i += 2) {
                $params[':round' . $i] = $votes[$i];
                $roundKeys[] = ':round' . $i;
            }
            
            $query = 'SELECT round_id FROM votes WHERE user_id = :userId AND round_id IN (' . implode(',', $roundKeys) . ') UNION ';
            $query .= 'SELECT round_id FROM round WHERE round_id IN (' . implode(',', $roundKeys) . ') AND round_final = 1';

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