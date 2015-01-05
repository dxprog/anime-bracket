<?php

namespace Controller {

    use Api;
    use Lib;

    class Landing extends Page {

        private static $_phrases = [ 'Because sometimes a poll just isn\'t good enough.',
                                     'Invite your friends! And then never speak to them again...',
                                     'Battle Royale, internet style.' ];

        public static function generate() {
            Lib\Display::setLayout('landing');
            Lib\Display::addKey('rounds', Api\Round::getRandomCompletedRounds(30));
            Lib\Display::addKey('phrase', static::$_phrases[rand() % count(static::$_phrases)]);
        }

    }

}