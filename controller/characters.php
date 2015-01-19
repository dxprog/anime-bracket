<?php

namespace Controller {

    use Api;
    use Lib;

    class Characters extends Page {
        public static function generate(array $params) {
            $bracket = Api\Bracket::getBracketByPerma(array_shift($params));
            if ($bracket) {
                Lib\Display::addKey('page', 'characters');
                $content = Lib\Display::renderAndAddKey('content', 'characters', (object) [
                    'bracket' => $bracket,
                    'characters' => Api\Character::getByBracketId($bracket->id)
                ]);
            }

        }
    }

}