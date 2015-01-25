<?php

namespace Controller {

    use Lib;

    class About extends Page {

        public static function generate(array $params) {
            $page = array_shift($params);
            $page = $page ?: 'about';
            $path = VIEW_PATH . '/' . $page . '.md';
            $out = 'Uh oh... that doesn\'t seem to exist...';

            if (is_readable($path)) {
                $file = file_get_contents($path);
                $out = Lib\Michelf\Markdown::defaultTransform($file);
            }

            Lib\Display::addKey('page', 'static');
            Lib\Display::renderAndAddKey('content', 'static', $out);

        }

    }

}