<?php

namespace Controller\Admin {
  use Api;
  use Lib;

  class Counts extends \Controller\Me {
    public static function generate(array $params) {
      $bracket = self::_getBracket(array_shift($params));
      if ($bracket) {
        if ($bracket->state === BS_ELIMINATIONS) {
          $characters = $bracket->getVoteAdjustedEliminationsCharacters();
          $tplData = (object)[
            'characters' => $characters,
            'bracket' => $bracket
          ];
          Lib\Display::renderAndAddKey('content', 'admin/eliminationCounts', $tplData);
        } else if ($bracket->state === BS_VOTING) {

        } else {
          header('Location: /me/');
        }
      }
    }
  }
}