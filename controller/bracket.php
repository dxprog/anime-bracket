<?php

namespace Controller {

  use Api;
  use Lib;

  class Bracket extends Page {
    public static function generate(array $params) {
      $bracket = Api\Bracket::getBracketByPerma(array_shift($params));
      if ($bracket) {
        Lib\Display::addKey('page', 'nominate');
        Lib\Display::addKey('title', $bracket->name . DEFAULT_TITLE_SUFFIX);
        Lib\Display::addKey('bracketNav', $bracket);
        Lib\Display::addKey('CANONICAL_PATH', '/' . $bracket->perma);
        $content = Lib\Display::renderAndAddKey('content', 'bracket', (object) [
            'bracket' => $bracket,
            'rules' => Lib\Michelf\Markdown::defaultTransform($bracket->rules)
        ]);
      } else {
        header('HTTP/1.1 404 Content Not Found');
        exit;
      }
    }
  }
}
