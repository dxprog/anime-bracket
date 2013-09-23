<?php

namespace Controller {
    
    use Lib;
    use Api;

    use stdClass;
    
    class User implements Page {
    
        public static function render() {

            $code = Lib\Url::Get('code', null);

            if ($code) {
                $success = Api\User::authenticateUser($code);
                if ($success) {
                    $redirect = Lib\Url::Get('state', '/');
                    header('Location: ' . $redirect);
                    exit;
                }
            } else {
                $obj = new stdClass;
                $obj->loginUrl = Api\User::getLoginUrl(Lib\Url::Get('redirect'));
                $obj->originalUrl = Lib\Url::Get('redirect');
                Lib\Display::setVariable('content', Lib\Display::compile($obj, 'login'));
            }

        }
        
        public static function registerExtension($class, $method, $type) {
            
        }

    }

}