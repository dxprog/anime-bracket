<?php

namespace Controller {

    use Lib;

    abstract class Page {

        public static final function render() {

            // Set some page default things
            Lib\Display::addKey('title', DEFAULT_TITLE);
            Lib\Display::setLayout('default');

            // Kick off page specific rendering
            static::generate();

        }

        public abstract static function generate();

    }

}