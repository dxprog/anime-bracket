<?php

namespace Controller\Admin {

  use Api;
  use Lib;

  class Rollback extends \Controller\Me {

    public static function generate(array $params) {
      $bracket = self::_getBracket(array_shift($params));
      if ($bracket) {
        if (!$_POST) {
          self::_displayForm($bracket);
        } else {

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

  }

}