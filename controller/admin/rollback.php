<?php

namespace Controller\Admin {

  use Api;
  use Lib;
  use stdClass;

  class Rollback extends \Controller\Me {

    public static function generate(array $params) {
      $bracket = self::_getBracket(array_shift($params));
      if ($bracket) {
        if (!$_POST) {
          self::_displayForm($bracket);
        } else {
          self::_rollback($bracket);
        }
      }
    }

    private static function _displayForm(Api\Bracket $bracket) {
      $pageData = [
        'bracket' => $bracket,
        'roundTitles' => Api\Round::getBracketFinalRoundTitles($bracket)
      ];
      Lib\Display::renderAndAddKey('content', 'admin/rollback', $pageData);
    }

    private static function _rollback(Api\Bracket $bracket) {
      $message = new stdClass;
      try {
        $rollbackIndex = Lib\Url::Post('round', true);
        $rounds = Api\Round::getBracketFinalRoundTitles($bracket);
        if (isset($rounds[$rollbackIndex])) {
          $round = $rounds[$rollbackIndex];
          $bracket->rollback($round->tier, $round->group);
          $message->type = 'success';
          $message->message = $bracket->name . ' rolled back to ' . $round->title;
        } else {
          $message->type = 'error';
          $message->message = 'Invalid round.';
        }
      } catch (Exception $exc) {
        $message->type = 'error';
        $message->message = $exc->message;
      }

      self::_createMessage($message->type, $message->message, true);
      self::_redirectToMain();
    }

  }

}