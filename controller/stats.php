<?php

namespace Controller {

  use Api;
  use Lib;

  class Stats extends Page {

    public static function generate(array $params) {

      $perma = array_shift($params);
      $bracket = Api\Bracket::getBracketByPerma($perma);
      if ($bracket) {

        // TODO - get full voting stats
        $entrantStats = Api\Stats::getEntrantPerformanceStats($bracket);
        Lib\Display::addKey('page', 'stats');
        Lib\Display::addKey('title', 'Stats for ' . $bracket->name . DEFAULT_TITLE_SUFFIX);
        Lib\Display::renderAndAddKey('content', 'stats', [
          'entrants' => $entrantStats,
          'bracket' => $bracket
        ]);

      }

    }

  }

}