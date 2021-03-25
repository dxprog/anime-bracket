<?php

namespace Controller {

    use Api;
    use Lib;

    class Characters extends Page {
        public static function generate(array $params) {
            $bracket = Api\Bracket::getBracketByPerma(array_shift($params));
            if ($bracket) {
                Lib\Display::addKey('page', 'characters');
                Lib\Display::addKey('CANONICAL_PATH', '/' . $bracket->perma . '/characters');
                Lib\Display::addKey('title', $bracket->name . ' Entrants' . DEFAULT_TITLE_SUFFIX);
                $hasSource = $bracket->hasSourceLabel();
                $hasSeed = $bracket->state == BS_VOTING || $bracket->state == BS_FINAL;
                Lib\Display::addKey('bracketNav', $bracket);

                $content = Lib\Display::renderAndAddKey('content', 'characters', (object) [
                    'bracket' => $bracket,
                    'characters' => Api\Character::getByBracketId($bracket->id),
                    'hasSource' => $hasSource,
                    'hasSeed' => $hasSeed,
                    'hasSorter' => $hasSource || $hasSeed
                ]);
            }

        }
    }

}
