<?php

namespace Controller\Admin {

    use Api;
    use Lib;
    use stdClass;

    class Stats extends \Controller\Me {

        public static function generate(array $params) {

            $bracket = self::_getBracket(array_shift($params));
            if ($bracket) {
                $stats = Api\Round::getVotingStats($bracket->id);
                if ($stats) {
                    $out = new stdClass;
                    $out->bracket = $bracket;
                    $out->stats = $stats;
                    Lib\Display::renderAndAddKey('content', 'admin/stats', $out);
                }
            }

        }

    }

}