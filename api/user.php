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

    /**
     * Reddit registration date of the account.
     * If 0, user is banned.
     */
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

      // If there is a user session, take a moment to verify the validity of that user
      if ($user instanceof User) {

      }

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
            if (!$user->sync()) {
              $user = null;
            }
          } else {

            // This is to update any records that were created before age was tracked
            if (!$user->age) {
              $user->age = (int) $data->created;
              $user->sync();
            }
          }

          // Save the login attempt before verifying attempt count
          self::_logLoginAttempt($user->id);

          // Now verify that the user isn't banned and hasn't tried logging in too much
          if (
            $user &&
            $user->age > 0 &&
            self::_verifyLoginAttempts($user->id)
          ) {
            Lib\Session::set('user', $user);
            $retVal = true;
          }
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

    /**
     * Logs a login attempt to the database
     */
    private static function _logLoginAttempt($userId) {
      $params = [
        'userId' => $userId,
        'date' => time(),
        'ip' => $_SERVER['REMOTE_ADDR']
      ];

      return Lib\Db::Query(
        'INSERT INTO `logins` (`user_id`, `login_date`, `login_ip`) VALUES (:userId, :date, :ip)',
        $params
      );
    }

    /**
     * Verifies that the user trying to login can do so given
     * the number of users per IP limitation
     */
    private static function _verifyLoginAttempts($userId) {
      $params = [
        'userId' => $userId,
        // Attempts within the last 24 hours
        'date' => time() - 86400,
        'ip' => $_SERVER['REMOTE_ADDR']
      ];

      // Find all login attempts that aren't this user
      $result = Lib\Db::Query(
        'SELECT user_id FROM `logins` WHERE `user_id` != :userId AND login_ip=:ip AND login_date >= :date',
        $params
      );

      // If the returned count is over the amount allowed (minus the currently logged in user), no es beuno
      return $result->count < MAX_USERS_SHARING_IP;
    }

  }

}
