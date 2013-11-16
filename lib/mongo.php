<?php

namespace Lib {
    
    use MongoClient;

    class Mongo {

        private static $_conn;
        private static $_db;

        public static function connect() {
            self::$_conn = new MongoClient();
        }

        public static function getDatabase() {
            $retVal = self::$_db;
            if (!$retVal && self::$_conn instanceof MongoClient) {
                self::$_db = self::$_conn->awwnimebracket;
                $retVal = self::$_db;
            }
            return $retVal;
        }

    }

    Mongo::connect();

}