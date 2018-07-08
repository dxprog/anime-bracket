<?php

namespace Controller\Admin {

    use Api;
    use Controller\Me;
    use Lib;

    /**
     * Controller to fix bracket state
     */
    class Normalize extends Me
    {
        public static function generate(array $params)
        {
            $transactions = [];
            $bracket = self::_getBracket(array_shift($params));

            if ($bracket) {
                // Invalidate cache for rounds data
                self::_refreshCaches($bracket);

                $bracketResults = $bracket->getResults(true);
                foreach ($bracketResults as $results) {
                    if (isset($results[0]->filler)) {
                        break;
                    }

                    // Apparently, tier 1 starts from 1 and uses odd number only
                    // so it needs different order divisor
                    $divisor = (int)$results[0]->order === 1 ? 4 : 2;

                    for ($i = 0, $count = count($results); $i < $count; $i += 2) {
                        $round = $results[$i];

                        if (!isset($round->filler) && $round->final) {
                            $query = Lib\Db::Query("SELECT round_id FROM `round` WHERE
                                    bracket_id = :bracketId AND
                                    round_tier = :tier AND
                                    round_group = :group AND
                                    round_order = :order
                                    LIMIT 1", [
                                ':bracketId' => $bracket->id,
                                ':tier' => $round->tier + 1,
                                ':group' => $round->group,
                                ':order' => (int)floor($round->order / $divisor),
                            ]);

                            if ((int)$query->count === 0) {
                                $newRound = new Api\Round();
                                $newRound->bracketId = $bracket->id;
                                $newRound->tier = $round->tier + 1;
                                $newRound->group = $round->group;
                                $newRound->order = (int)floor($round->order / $divisor);
                                $newRound->character1Id = self::_getWinner($results[$i]);
                                $newRound->character2Id = self::_getWinner($results[$i + 1]);
                                $newRound->sync();
                            }

                            $transactions[] = [
                                "UPDATE `round` SET round_final = TRUE WHERE round_id = :id",
                                [':id' => $results[$i]->id]
                            ];
                            $transactions[] = [
                                "UPDATE `round` SET round_final = TRUE WHERE round_id = :id",
                                [':id' => $results[$i + 1]->id]
                            ];
                        }
                    }
                }

                if (count($transactions) > 0) {
                    Lib\Db::BulkQuery($transactions);
                }
            }

            // Invalidate cache for results data
            $bracket->getResults(true);
            self::_redirectToMain();
        }

        /**
         * Determine round winner based on largest vote count or smallest seed number (in that order)
         * @param Api\Round $round
         * @return mixed
         */
        private static function _getWinner(Api\Round $round)
        {
            $voteLeft = (int)$round->character1Votes > (int)$round->character2Votes;
            $seedLeft = (int)$round->character1->seed > (int)$round->character2->seed;
            $sameVote = (int)$round->character1Votes === (int)$round->character2Votes;
            if ($voteLeft || ($sameVote && $seedLeft)) {
                $result = $round->character1->id;
            } else {
                $result = $round->character2->id;
            }

            return $result;
        }
    }
}
