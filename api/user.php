<?php

namespace Api {
    
    use Lib;

    class User extends Lib\Dal {

        protected $_dbTable = 'users';
        protected $_dbPrimaryKey = 'id';
        protected $_dbMap = [
            'id' => 'user_id',
            'name' => 'user_name',
            'admin' => 'user_admin',
            'ip' => 'user_ip',
            'prizes' => 'user_prizes'
        ];

        public $id = 0;
        public $name;
        public $admin = false;
        public $ip;
        public $prizes;

        public static function getByName($userName) {
            $retVal = null;
            $result = Lib\Db::Query('SELECT * FROM users WHERE user_name = :userName', [ ':userName' => $userName ]);
            if ($result && $result->count) {
                $retVal = new User(Lib\Db::Fetch($result));
            }
            return $retVal;
        }

        public static function getCurrentUser() {
            return isset($_SESSION['user']) ? $_SESSION['user'] : null;
        }

        public static function getLoginUrl($redirect = '') {
            $client = new Lib\OAuth2\Client(REDDIT_TOKEN, REDDIT_SECRET, Lib\OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
            return $client->getAuthenticationUrl('https://ssl.reddit.com/api/v1/authorize', REDDIT_HANDLER, [ 'scope' => 'identity', 'state' => $redirect ]);
        }

        public static function authenticateUser($code) {
            $retVal = false;
            $client = new Lib\OAuth2\Client(REDDIT_TOKEN, REDDIT_SECRET, Lib\OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
            $response = $client->getAccessToken('https://ssl.reddit.com/api/v1/access_token', 'authorization_code', [ 'code' => $code, 'redirect_uri' => REDDIT_HANDLER ]);
            if (isset($response['result']['access_token'])) {
                $client->setAccessToken($response['result']['access_token']);
                $client->setAccessTokenType(Lib\OAuth2\CLient::ACCESS_TOKEN_BEARER);
                $response = $client->fetch('https://oauth.reddit.com/api/v1/me.json');
                if (isset($response['result']['name'])) {
                    $result = $response['result'];
                    $user = self::getByName($result['name']);
                    if (!$user && $result['created'] - REDDIT_MINAGE > 0) {
                        $user = new User;
                        $user->name = $result['name'];
                        $user->ip = $_SERVER['REMOTE_ADDR'];
                        if ($user->sync()) {
                            $retVal = true;
                        }
                    } else if ($user) {
                        $retVal = true;
                    }
                }

                if ($retVal) {
                    $_SESSION['user'] = $user;
                }
            }
            return $retVal;
        }

    }

}