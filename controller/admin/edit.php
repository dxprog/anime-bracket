<?php

namespace Controller\Admin {

    use Api;
    use Lib;

    class Edit extends \Controller\Me {

        public static function generate(array $params) {

            $bracket = self::_getBracket(array_shift($params));

            if ($bracket) {

                // Create the bracket on POST
                if ($_POST) {

                    $id = Lib\Url::Post('bracketId', true);
                    $name = Lib\Url::Post('name');
                    $rules = Lib\Url::Post('rules');

                    if ($name && $rules) {
                        $bracket->name = trim($name);
                        $bracket->rules = $rules;
                        $bracket->nameLabel = Lib\Url::Post('nameLabel');
                        $bracket->blurb = Lib\Url::Post('blurb');

                        $showSource = Lib\Url::Post('showSource') === 'on';
                        $bracket->sourceLabel = $showSource ? Lib\Url::Post('sourceLabel') : 'NO_SOURCE';
                        $bracket->captcha = Api\Bracket::$CAPTCHA_STATUS[Lib\Url::Post('captcha')];

                        $bracket->minAge = Lib\Url::Post('minAge', true);

                        $advanceHour = Lib\Url::Post('advanceHour', true);
                        $advanceHour = null !== $advanceHour ? $advanceHour : -1;
                        $bracket->advanceHour = $advanceHour;

                        if ($bracket->sync()) {
                            // Clear the generic bracket related caches
                            self::_refreshCaches($bracket);
                            header('Location: /me/?edited');
                            exit;
                        }

                    }

                }

                $bracket->sourceHidden = $bracket->sourceLabel === 'NO_SOURCE';
                $bracket->captchaNever = !$bracket->captcha;
                $bracket->captchaRandom = $bracket->captcha == Api\Bracket::$CAPTCHA_STATUS['RANDOM'];
                $bracket->captchaAlways = $bracket->captcha == Api\Bracket::$CAPTCHA_STATUS['ALWAYS'];
                $bracket->times = self::_generateAdvanceTimes($bracket->advanceHour);
                $bracket->ages = self::_generateAges($bracket->minAge);
                Lib\Display::renderAndAddKey('content', 'admin/bracket', $bracket);

            }

        }

    }

}