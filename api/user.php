<?php

namespace Api {

    use Lib;
    use OAuth2;

    class User extends Lib\Dal {

        protected $_dbTable = 'users';
        protected $_dbPrimaryKey = 'id';
        protected $_dbMap = [
            'id' => 'user_id',
            'name' => 'user_name',
            'admin' => 'user_admin',
            'ip' => 'user_ip',
            'age' => 'user_age'
        ];

        public $id = 0;
        public $name;
        public $admin = false;
        public $ip;
        public $age;

        public function __construct($row = null) {
            parent::__construct($row);
            if (is_object($row)) {
                $this->admin = ((int) $row->user_admin) === 1;
            }
        }

        public static function getByName($userName) {
            $retVal = null;
            $result = Lib\Db::Query('SELECT * FROM users WHERE user_name LIKE :userName', [ ':userName' => $userName ]);
            if ($result && $result->count) {
                $retVal = new User(Lib\Db::Fetch($result));
            }
            return $retVal;
        }

        public static function getCurrentUser() {
            $user = Lib\Session::get('user');
            return $user;
        }

        /**
         * Returns the login URL for OAuth2 authentication
         */
        public static function getLoginUrl($redirect = '') {
            $client = self::_createOAuth2();
            return $client->getLoginUrl('temporary', [
                'identity'
            ], $redirect);
        }

        public function logout() {
            Lib\Session::set('user', null);
        }

        /**
         * OAuth2 response handler
         */
        public static function authenticateUser($code) {
            $retVal = false;
            $client = self::_createOAuth2();

            if ($client->getToken($code)) {
                $data = $client->call('api/v1/me');
                if ($data && isset($data->name)) {
                    $user = self::getByName($data->name);
                    if (!$user) {
                        $user = new User;
                        $user->name = $data->name;
                        $user->age = (int) $data->created;
                        $user->ip = $_SERVER['REMOTE_ADDR'];
                        if ($user->sync()) {
                            $retVal = true;
                        }
                    } else {

                        // This is to update any records that were created before age was tracked
                        if (!$user->age) {
                            $user->age = (int) $data->created;
                            $user->sync();
                        }
                        $retVal = true;
                    }

                    Lib\Session::set('user', $user);
                }
            }

            return $retVal;
        }

        private static function _createOAuth2(User $user = null) {
            $retVal = new Lib\RedditOAuth(REDDIT_TOKEN, REDDIT_SECRET, HTTP_UA, REDDIT_HANDLER);

            // If we have stashed tokens, set those up as well
            if ($user && $user->token && $user->refreshToken && $user->tokenExpires) {
                $retVal->setToken($user->token);
                $retVal->setRefreshToken($user->refreshToken);
                $retVal->setExpiration($user->tokenExpires);
            }

            return $retVal;
        }

    }

}