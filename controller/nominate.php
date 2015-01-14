<?php

namespace Controller {

    use Api;
    use Lib;

    class Nominate extends Page {

        public static function generate(array $params) {
            self::_checkLogin();
            $bracket = Api\Bracket::getBracketByPerma(array_shift($params));
            self::_enableAd();
            if ($bracket) {
                $out = (object)[
                    'rules' => Lib\Michelf\Markdown::defaultTransform($bracket->rules),
                    'bracket' => $bracket
                ];
                Lib\Display::addKey('page', 'nominate');
                Lib\Display::renderAndAddKey('content', 'nominate', $out);
            }
        }

    }

}