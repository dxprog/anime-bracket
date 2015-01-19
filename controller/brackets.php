<?php

namespace Controller {

    use Api;
    use Lib;

    class Brackets extends Page {

        public static function generate(array $params) {

            $active = array_shift($params) !== 'past';

            $brackets = Lib\Cache::fetch(function() use ($active) {

                $allBrackets = Api\Bracket::getAll();

                // Filter out active/completed brackets
                $brackets = [];
                foreach ($allBrackets as $bracket) {
                    if ($active && ($bracket->state == BS_ELIMINATIONS || $bracket->state == BS_VOTING || $bracket->state == BS_NOMINATIONS)) {
                        $brackets[] = $bracket;
                    }

                    if (!$active && $bracket->state == BS_FINAL) {
                        $brackets[] = $bracket;
                    }
                }

                // Check for card images
                foreach ($brackets as $bracket) {
                    if (is_readable('./images/bracket_' . $bracket->id . '_card.jpg')) {
                        $bracket->cardImage = '/images/bracket_' . $bracket->id . '_card.jpg';
                    } else {
                        $bracket->entrants = Api\Character::getRandomCharacters($bracket, 9);
                    }
                }

                // Sort the brackets by reverse date
                usort($brackets, function($a, $b) {
                    return $a->start > $b->start ? -1 : 1;
                });

                return $brackets;

            }, 'Controller::Brackets_displayBrackets_' . ($active ? 'active' : 'completed'));

            Lib\Display::addKey('page', 'brackets');
            $title = $active ? 'Current Brackets' : 'Past Brackets';
            Lib\Display::renderAndAddKey('content', 'bracketsView', [ 'brackets' => $brackets, 'title' => $title ]);

        }

    }

}