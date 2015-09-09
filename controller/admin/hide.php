<?php

namespace Controller\Admin {

    use Api;
    use Lib;

    class Hide extends \Controller\Me {

        public static function generate(array $params) {

            $bracket = self::_getBracket(array_shift($params));
            if ($bracket) {
                // Cast to int to make the database happy
                $bracket->hidden = (int) !$bracket->hidden;
                if ($bracket->sync()) {
                    self::_createMessage('success', $bracket->hidden ? 'The bracket is now hidden to all users' : 'The bracket is now visible to all users', true);
                    self::_refreshCaches($bracket);
                    header('Location: /me/');
                    exit;
                } else {
                    print_r(Lib\Db::$lastError);
                }
            }

        }

    }

}