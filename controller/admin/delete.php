<?php

namespace Controller\Admin {

    use Api;

    class Delete extends \Controller\Me {

        public static function generate(array $params) {

            $bracket = self::_getBracket(array_shift($params));
            if ($bracket && $bracket->state != BS_FINAL) {
                $bracket->delete();
                self::_refreshCaches();
                header('Location: /me/');
            }

        }

    }

}