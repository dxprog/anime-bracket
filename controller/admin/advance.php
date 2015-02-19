<?php

namespace Controller\Admin {

    use Api;
    use Lib;
    use stdClass;

    class Advance extends \Controller\Me {

        public static function generate(array $params) {

            $message = null;
            $bracket = self::_getBracket(array_shift($params));
            if ($bracket) {
                $bracket->advance();
                $message = new stdClass;
                $message->type = 'success';
                $message->message = $bracket->name . ' has advanced to the next round';

                self::_refreshCaches($bracket);

            }

            return self::_main($message, true);

        }

    }

}