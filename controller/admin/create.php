<?php

namespace Controller\Admin {

    use Api;
    use Lib;

    class Create extends \Controller\Me {

        public static function generate(array $params) {
            // Create the bracket on POST
            if ($_POST) {

                $name = Lib\Url::Post('name');
                $rules = Lib\Url::Post('rules');

                if ($name && $rules) {
                    $bracket = new Api\Bracket();
                    $bracket->name = trim($name);
                    $bracket->rules = $rules;
                    $bracket->state = 0;
                    $bracket->start = time();
                    $bracket->generatePerma();
                    $bracket->nameLabel = Lib\Url::Post('nameLabel');
                    $bracket->minAge = Lib\Url::Post('minAge', true);

                    $hideSource = Lib\Url::Post('hideSource') === 'on';
                    $bracket->sourceLabel = $hideSource ? 'NO_SOURCE' : Lib\Url::Post('sourceLabel');
                    $bracket->captcha = Api\Bracket::$CAPTCHA_STATUS[Lib\Url::Post('captcha')];

                    $advanceHour = Lib\Url::Post('advanceHour', true);
                    if ($advanceHour !== null) {
                        $utcOffset = Lib\Url::Post('utcOffset', true);
                        $advanceHour += $utcOffset !== null ? $utcOffset : 0;
                    } else {
                        $advanceHour = -1;
                    }
                    $bracket->advanceHour = $advanceHour;

                    if ($bracket->sync()) {
                        $bracket->addUser(self::$_user);

                        self::_refreshCaches();

                        // Clear the generic bracket related caches
                        header('Location: /me/?created');
                        exit;
                    }

                }

            }

            // Or display the form
            $_POST['times'] = self::_generateAdvanceTimes();
            $_POST['ages'] = self::_generateAges(REDDIT_MINAGE);
            Lib\Display::renderAndAddKey('content', 'admin/bracket', $_POST);
        }

    }

}