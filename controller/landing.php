<?php

namespace Controller {

  use Api;
  use Lib;

  class Landing extends Page {
    public static function generate(array $params) {
      $out = Lib\Cache::getInstance()->fetch(function() {
        $featured = Api\Bracket::queryReturnAll(
          [
            'state' => [ 'in' => [ BS_ELIMINATIONS, BS_VOTING ] ],
            'hidden' => 0
          ],
          [ 'score' => 'desc' ], 1);
        $featured = $featured[0];
        $featuredCharacters = Api\Character::queryReturnAll([ 'bracketId' => $featured->id, 'seed' => [ 'gt' => 0 ] ], [ 'seed' => 'asc' ], 100);
        usort($featuredCharacters, function($a, $b) {
          return rand() < 0.5 ? -1 : 1;
        });

        return (object)[
          'featuredBracket' => $featured,
          'topFour' => array_slice($featuredCharacters, 0, 4),
          'featuredCharacters' => $featuredCharacters,
          'topBrackets' => self::getPopularBrackets(),
          'pastBrackets' => self::getPastBrackets()
        ];
      }, 'Landing', CACHE_LONG);
      Lib\Display::addKey('page', 'landing');
      Lib\Display::renderAndAddKey('content', 'landing', $out);
    }

    /**
     * Returns the top five popular completed brackets and their winners
     */
    private static function getPastBrackets() {
      $pastBrackets = Api\Bracket::queryReturnAll([ 'state' => BS_FINAL ], [ 'score' => 'desc' ], 5);
      $winnerIds = array_map(function($bracket) {
        return $bracket->winnerCharacterId;
      }, $pastBrackets);
      $tmp = Api\Character::queryReturnAll([ 'id' => [ 'in' => $winnerIds ] ]);
      $winners = [];
      foreach ($tmp as $character) {
        $winners[$character->id] = $character;
      }

      foreach ($pastBrackets as $bracket) {
        $bracket->winner = $winners[$bracket->winnerCharacterId];
      }

      return $pastBrackets;
    }

    /**
     * Gets the top five popular brackets with some characters to represent
     */
    private static function getPopularBrackets() {
      $topBrackets = Api\Bracket::queryReturnAll([ 'state' => [ 'in' => [ BS_ELIMINATIONS, BS_VOTING ] ] ], [ 'score' => 'desc' ], 6);
      // Shift off the featured bracket
      array_shift($topBrackets);

      // Five DB calls to get a representing entrant...
      foreach ($topBrackets as $bracket) {
        $character = Api\Character::queryReturnAll([ 'bracketId' => $bracket->id ], [ 'id' => 'asc' ], 1);

        // Just shove the character in the "winner" property. I know what I mean by it...
        $bracket->winner = $character[0];
      }

      return $topBrackets;
    }

  }

}