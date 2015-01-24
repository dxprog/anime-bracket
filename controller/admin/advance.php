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

                Lib\Cache::setDisabled(true);
                Api\Bracket::getAll();
                Api\Bracket::getBracketByPerma($bracket->perma);
                Api\Round::getCurrentRounds($bracket->id);
                \Controller\Brackets::generate([ 'past' ]);
                \Controller\Brackets::generate([]);
                Lib\Cache::setDisabled(false);

            }

            return self::_main($message, true);

        }

    }

}