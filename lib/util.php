<?php

namespace Lib {

    define('MINUTE_SECONDS', 60);
    define('HOUR_SECONDS', MINUTE_SECONDS * 60);
    define('DAY_SECONDS', HOUR_SECONDS * 24);
    define('MONTH_SECONDS', DAY_SECONDS * 30);
    define('YEAR_SECONDS', MONTH_SECONDS * 12);

    /**
     * Generic utilities library (because I don't want to spawn individual classes for different kinds. Also, I'm lazy)
     */
    class Util {

        public static function relativeTime($timestamp) {

            $delta = time() - $timestamp;
            $unit = 'second';
            $amount = $delta;

            if ($delta >= YEAR_SECONDS) {
                $amount = floor($delta / YEAR_SECONDS);
                $unit = 'year';
            } else if ($delta >= MONTH_SECONDS) {
                $amount = floor($delta / MONTH_SECONDS);
                $unit = 'month';
            } else if ($delta >= DAY_SECONDS) {
                $amount = floor($delta / DAY_SECONDS);
                $unit = 'day';
            } else if ($delta >= HOUR_SECONDS) {
                $amount = floor($delta / HOUR_SECONDS);
                $unit = 'hour';
            } else if ($delta >= MINUTE_SECONDS) {
                $amount = floor($delta / MINUTE_SECONDS);
                $unit = 'minute';
            }

            return $amount . ' ' . $unit . ((int) $amount !== 1 ? 's' : '');

        }

    }

}