<?php

namespace Lib {

    define('KEY_CLIENT_DATA', 'clientData');

    use Handlebars\Handlebars;
    use stdClass;

    class Display {

        private static $_tplData = [];
        private static $_theme;
        private static $_layout;
        private static $_hbEngine = null;
        private static $_viewPath = null;

        public static function init() {

            self::$_hbEngine = new Handlebars([
                'loader' => new \Handlebars\Loader\FilesystemLoader(VIEW_PATH),
                'partials_loader' => new \Handlebars\Loader\FilesystemLoader(VIEW_PATH . '/partials/')
            ]);
            self::addKey(KEY_CLIENT_DATA, new stdClass);
            self::_addStandardHelpers();
        }

        /**
         * Renders the page
         **/
        public static function render() {
            echo self::$_hbEngine->render('layouts/' . self::$_layout . '.handlebars', self::$_tplData);
        }

        /**
         * Renders an object out as JSON and ends the request
         */
        public static function renderJson($obj) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($obj);
            exit;
        }

        // Displays an error message and halts rendering
        public static function showError($code, $message) {
            // NOOP until I can figure out what to do with this
        }

        public static function setTheme($name) {
            self::$_theme = $name;
        }

        public static function setLayout($name) {
            self::$_layout = $name;
        }

        /**
         * Renders data against a template and adds it to the output object
         * @param string $key Key found in the layout template
         * @param string $template Template name to render
         * @param string $data Data to render against template
         */
        public static function renderAndAddKey($key, $template, $data) {
            self::addKey($key, self::$_hbEngine->render($template, $data));
        }

        /**
         * Adds a key/value to the output data
         * @param string $key Key found in the layout template
         * @param string $template Data to associate to the key
         */
        public static function addKey($key, $value) {
            self::$_tplData[$key] = $value;
        }

        /**
         * Adds data to the outgoing client side data blob
         */
        public static function addClientData($key, $obj) {
            self::$_tplData[KEY_CLIENT_DATA]->$key = $obj;
        }

        /**
         * Adds a helper to the Handlebars engine
         */
        public static function addHelper($name, $function) {
            self::$_hbEngine->addHelper($name, $function);
        }

        /**
         * Adds a set of standard utility helpers to the render engine
         */
        private static function _addStandardHelpers() {
            self::addHelper('relativeTime', function($template, $context, $args, $source) {
                return Util::relativeTime($context->get($args));
            });

            // Idea lifted right out of dust.js
            self::addHelper('sep', function($template, $context, $args, $source) {
                if (!$context->get('@last')) {
                    return $source;
                }
            });

            self::addHelper('jsonBlob', function($template, $context, $args, $source) {
                return json_encode($context->get($args));
            });
        }

    }

    Display::init();

}