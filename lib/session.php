<?php

namespace Lib {

    use stdClass;

    define('SESSION_NAME', 'AB_SESS');
    define('SESSION_EXPIRE', 86400 * 365); // session lasts for a year

    class Session {

        private static $_sess;
        private static $_id;

        public static function start() {
            self::$_id = Url::Get(SESSION_NAME, null, $_COOKIE);

            if (!self::$_id) {
                self::$_id = bin2hex(openssl_random_pseudo_bytes(32));
                setcookie(SESSION_NAME, self::$_id, time() + SESSION_EXPIRE, '/', SESSION_DOMAIN);
            }
            self::$_sess = Cache::Get(SESSION_NAME . '_' . self::$_id, true);
        }

        public static function get($key) {
            $retVal = null;
            if (self::$_sess instanceof stdClass && isset(self::$_sess->$key)) {
                $retVal = self::$_sess->$key;
            }
            return $retVal;
        }

        public static function set($key, $value) {
            if (!self::$_sess instanceof stdClass) {
                self::$_sess = new stdClass;
            }
            self::$_sess->$key = $value;
            Cache::Set(SESSION_NAME . '_' . self::$_id, self::$_sess, SESSION_EXPIRE);
        }

    }

}