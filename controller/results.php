<?php

namespace Controller {

    use Api;
    use Lib;

    class Results extends Page {

        public static function generate(array $params) {

            $perma = array_shift($params);
            $bracket = Api\Bracket::getBracketByPerma($perma);
            if ($bracket) {
                $bracket->results = $bracket->getResults();
                $user = Api\User::getCurrentUser();
                if ($user) {
                    $bracket->userVotes = $bracket->getVotesForUser($user);
                }
                Lib\Display::addKey('page', 'results');
                Lib\Display::renderAndAddKey('content', 'results', $bracket);
            }

        }

    }

}