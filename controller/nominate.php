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
                $bracket->nameLabel = $bracket->nameLabel ?: 'Character name';
                $bracket->sourceLabel = $bracket->sourceLabel ?: 'Source';
                $bracket->sourceLabel = $bracket->sourceLabel === 'NO_SOURCE' ? false : $bracket->sourceLabel;

                $user = Api\User::getCurrentUser();
                $out = (object)[
                    'rules' => Lib\Michelf\Markdown::defaultTransform($bracket->rules),
                    'bracket' => $bracket,
                    'csrfToken' => $user->csrfToken
                ];

                Lib\Display::addKey('page', 'nominate');
                Lib\Display::addKey('bracketNav', $bracket);
                Lib\Display::addKey('CANONICAL_PATH', '/' . $bracket->perma . '/nominate');
                Lib\Display::renderAndAddKey('content', 'nominate', $out);
            }
        }

    }

}
