<?php

namespace Lib {

    use stdClass;

    define('TEST_BUCKET_CACHE_KEY', 'bucket_tests');
    define('DEFAULT_TEST_VALUE', 'control');

    class TestBucket {

        private static $_tests;
        private static $_initialized = false;
        private static $_seed = false;

        /**
         * Loads the test buckets and sets the bucket seed
         */
        public static function initialize($seed = null) {

            if (!self::$_initialized) {
                self::$_tests = Cache::fetch(function() {
                    return json_decode(@file_get_contents('buckets.json'));
                }, TEST_BUCKET_CACHE_KEY);
                self::$_initialized = true;

                // Add template helpers
                Display::addHelper('inTestBucket', function($template, $context, $args, $source) {

                    if (preg_match_all('/([\w]+)=\"([^\"]+)\"/i', $args, $matches)) {
                        $args = new stdClass;
                        for ($i = 0, $count = count($matches[0]); $i < $count; $i++) {
                            $key = $matches[1][$i];
                            $args->$key = str_replace('"', '', $matches[2][$i]);
                        }

                        if (isset($args->key) && isset($args->value)) {
                            if (self::get($args->key) == $args->value) {
                                return $template->render($context);
                            }
                        } else {
                            throw new Exception('inTestBucket requires "key" and "test" parameters');
                        }

                    }

                });

            }

            if ($seed || !self::$_seed) {
                self::$_seed = $seed ?: (int) str_replace('.', '', $_SERVER['REMOTE_ADDR']);
            }

        }

        /**
         * Gets a test bucket value for the given seed
         */
        public static function get($key, $seed = null) {

            self::initialize();

            $seed = $seed ?: self::$_seed;
            $cacheKey = 'test_' . $key . '_' . $seed;

            return Cache::fetch(function() use ($key, $seed) {
                $retVal = DEFAULT_TEST_VALUE;
                $found = false;

                if (isset(self::$_tests->$key)) {
                    $test = self::$_tests->$key;
                    if (isset($test->whiteLists)) {
                        foreach ($test->whiteLists as $whiteList) {
                            if (in_array($seed, $whiteList->ids)) {
                                $retVal = $whiteList->value;
                                $found = true;
                                break;
                            }
                        }
                    }

                    if (!$found && isset($test->ramps)) {
                        srand($seed);
                        $rand = rand() % 100;
                        $percentTotal = 0;
                        foreach ($test->ramps as $ramp) {
                            $percentTotal += $ramp->percent;
                            if ($rand <= $percentTotal) {
                                $retVal = $ramp->value;
                                break;
                            }
                        }
                    }
                }

                return $retVal;
            }, $cacheKey);
        }

    }

}