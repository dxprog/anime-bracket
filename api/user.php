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
            $result = Lib\Db::Query('SELECT * FROM users WHERE user_name = :userName', [ ':userName' => $userName ]);
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
            $auth = new OAuth2\Strategy\AuthCode($client);
            return $auth->authorizeUrl([
                'scope' => 'identity',
                'state' => $redirect,
                'redirect_uri' => REDDIT_HANDLER
            ]);
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
            $auth = new OAuth2\Strategy\AuthCode($client);

            try {
                $token = $auth->getToken($code, [ 'redirect_uri' => REDDIT_HANDLER ]);
                if ($token) {
                    $response = $token->get('https://oauth.reddit.com/api/v1/me.json');
                    $data = json_decode($response->body());
                    if ($data) {

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
            } catch (Exception $e) {

            }

            return $retVal;
        }

        private static function _createOAuth2() {
            return new OAuth2\Client(REDDIT_TOKEN, REDDIT_SECRET, [
                'site' => 'https://ssl.reddit.com/api/v1',
                'authorize_url' => '/authorize',
                'token_url' => '/access_token'
            ]);
        }

    }

}