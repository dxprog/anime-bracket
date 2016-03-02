<?php

namespace Controller\Admin {

  use Api;
  use Lib;

  class Users extends \Controller\Me {

    public static function generate(array $params) {
      $bracket = self::_getBracket(array_shift($params));
      if (empty($_POST)) {
        self::_displayUsers($bracket);
      }
    }

    private static function _displayUsers(Api\Bracket $bracket) {
      $users = $bracket->getUsers();

      // Identify the current user in the lot
      foreach ($users as $user) {
        if ($user->id === self::$_user->id) {
          $user->self = true;
          break;
        }
      }

      Lib\Display::renderAndAddKey('content', 'admin/users', (object)[ 'users' => $users ]);
    }

    private static function _addUser() {

    }

    private static function _removeUser() {

    }

  }

}