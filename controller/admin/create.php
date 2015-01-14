<?php

namespace Controller\Admin {

    use Api;
    use Lib;

    class Create extends \Controller\Me {

        public static function generate(array $params) {
            // Create the bracket on POST
            if ($_POST) {

                $name = Lib\Url::Post('bracketName', null);
                $rules = Lib\Url::Post('rules', null);

                if ($name && $rules) {
                    $bracket = new Api\Bracket();
                    $bracket->name = trim($name);
                    $bracket->rules = $rules;
                    $bracket->state = 0;
                    $bracket->start = time();
                    $bracket->generatePerma();

                    if ($bracket->sync()) {
                        $bracket->addUser(self::$_user);
                        header('Location: /me/?flushCache');
                        exit;
                    }

                }

            }

            // Or display the form
            Lib\Display::renderAndAddKey('content', 'admin/bracket', $_POST);
        }

    }

}