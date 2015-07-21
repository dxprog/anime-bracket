<?php

namespace Controller {

    use Lib;
    use Api;

    use stdClass;

    class User extends Page {

        public static function generate(array $params) {

            $code = Lib\Url::Get('code', null);
            $action = array_shift($params);

            if ($action === 'logout') {
                $user = Api\User::getCurrentUser();
                if ($user) {
                    $user->logout();
                    header('Location: /brackets/');
                }
            }

            if ($code) {
                $success = Api\User::authenticateUser($code);
                if ($success) {
                    $redirect = Lib\Url::Get('state', '/');
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    Lib\Display::addKey('content', 'We were unable to verify your account at this time or your account age does not meet the requirements.');
                }
            } else {
                $obj = new stdClass;
                $obj->loginUrl = Api\User::getLoginUrl(Lib\Url::Get('redirect'));

                // Do a mobile check
                if (preg_match('/iphone|android|windows phone/i', $_SERVER['HTTP_USER_AGENT'])) {
                    $obj->loginUrl = str_replace('authorize', 'authorize.compact', $obj->loginUrl);
                }

                $obj->originalUrl = Lib\Url::Get('redirect');
                Lib\Display::addKey('page', 'login');
                Lib\Display::addKey('title', 'Login' . DEFAULT_TITLE_SUFFIX);
                Lib\Display::renderAndAddKey('content', 'login', $obj);
            }

        }

    }

}