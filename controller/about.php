<?php

namespace Controller {

    use Lib;

    class About extends Page {

        public static function generate(array $params) {
            $page = array_shift($params);
            $page = $page ?: 'about';
            $path = VIEW_PATH . '/' . $page . '.md';
            $out = (object) [
              'content' => 'Uh oh... that doesn\'t seem to exist...'
            ];

            if (is_readable($path)) {
                $file = file_get_contents($path);

                // Extract out a page header
                if (preg_match('/^##[\w]/', $file)) {
                  $lines = explode(PHP_EOL, $file);
                  $header = substr(array_shift($lines), 2);
                  // Browser page title
                  Lib\Display::addKey('title', $header . DEFAULT_TITLE_SUFFIX);
                  $out->header = $header;
                  $file = implode(PHP_EOL, $lines);
                  unset($lines);
                }

                $out->content = Lib\Michelf\Markdown::defaultTransform($file);
            }

            Lib\Display::addKey('page', 'static');
            Lib\Display::renderAndAddKey('content', 'static', $out);

        }

    }

}