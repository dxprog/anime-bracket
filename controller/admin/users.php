<?php

namespace Controller\Admin {

  use Api;
  use Lib;

  class Users extends \Controller\Me {

    public static function generate(array $params) {
      $bracket = self::_getBracket(array_shift($params));
      $message = null;
      if (!empty($_POST)) {
        $action = Lib\Url::Get('action');
        if ($action === 'add') {
          $message = self::_addUser($bracket);
        } else if ($action === 'remove') {
          $message = self::_removeUser($bracket);
        }
      }

      self::_displayUsers($bracket, $message);
    }

    private static function _displayUsers(Api\Bracket $bracket, $message = null) {
      $users = $bracket->getUsers();

      // Identify the current user in the lot
      foreach ($users as $user) {
        if ($user->id === self::$_user->id) {
          $user->self = true;
          break;
        }
      }

      Lib\Display::renderAndAddKey('content', 'admin/users', (object)[
        'users' => $users,
        'message' => $message,
        'bracket' => $bracket
      ]);
    }

    /**
     * Adds a user to the current bracket
     * @param Api\Bracket $bracket The bracket to add the user to
     * @return stdClass The error/success message to display
     */
    private static function _addUser(Api\Bracket $bracket) {
      $retVal = null;
      $user = Lib\Url::Post('username');
      if ($user) {
        $user = preg_replace('/^\/?u\//i', '', $user);
        $user = Api\User::getByName($user);
        if ($user) {
          if ($bracket->addUser($user)) {
            $retVal = parent::_createMessage('success', 'User /u/' . $user->name . ' has been added as an admin of ' . $bracket->name . '!');
          } else {
            $retVal = parent::_createMessage('error', 'There was an error adding /u/' . $user->name . ' as an admin.');
          }
        } else {
          $retVal = parent::_createMessage('error', '/u/' . $user . ' was not found in the system. Maybe they haven\'t logged into AnimeBracket?');
        }
      } else {
        $retVal = parent::_createMessage('error', 'You must provide a user to add');
      }
      return $retVal;
    }

    /**
     * Removes a user from the current bracket
     * @param Api\Bracket $bracket The bracket to remove the user from
     * @return stdClass The error/success message to display
     */
    private static function _removeUser(Api\Bracket $bracket) {
      $retVal = null;
      $userId = Lib\Url::Post('id', true);
      if ($userId) {
        $user = Api\User::getById($userId);
        if ($user) {
          if ($bracket->removeUser($user)) {
            $retVal = parent::_createMessage('success', 'User /u/' . $user->name . ' removed from bracket');
          } else {
            $retVal = parent::_createMessage('error', 'There was an error removing /u/' . $user->name . '.');
          }
        } else {
          $retVal = parent::_createMessage('error', 'Invalid user ID');
        }
      } else {
        $retVal = parent::_createMessage('error', 'Invalid user ID');
      }
      return $retVal;
    }

  }

}