<?php

namespace Controller\Admin {

    use Api;
    use Lib;

    class Download extends \Controller\Me {

        public static function generate(array $params) {

            $bracket = self::_getBracket(array_shift($params));

            if ($bracket) {

                $handle = fopen('php://output', 'wb');
                
                if ($handle) {
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename=' . $bracket->perma . '.csv');

                    // I generally don't like doing queries in a controller, but it's going
                    // to be much lighter weight to dump the data from the query directly
                    // out to the stream.
                    $query  = 'SELECT v.vote_date, c.character_name, r.round_tier, r.round_group ';
                    $query .= 'FROM `votes` v INNER JOIN `round` r ON r.round_id = v.round_id ';
                    $query .= 'INNER JOIN `character` c ON c.character_id = v.character_id ';
                    $query .= 'WHERE v.bracket_id = :bracketId';
                    
                    $result = Lib\Db::Query($query, [ ':bracketId' => $bracket->id ]);

                    if ($result && $result->count) {
                        fputcsv($handle, [ 'Date', 'Entrant', 'Round', 'Group' ]);
                        while ($row = Lib\Db::Fetch($result)) {
                            fputcsv($handle, [ date('c', $row->vote_date), $row->character_name, $row->round_tier, $row->round_group ]);
                        }
                    }

                    fclose($handle);

                }


            }

            exit;

        }

    }

}