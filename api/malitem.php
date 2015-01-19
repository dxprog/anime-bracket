<?php

namespace Api {

    use Lib;

    class MalItem extends Lib\Dal {

        protected $_dbTable = 'mal_items';
        protected $_dbPrimaryKey = 'id';
        protected $_dbMap = [
            'id' => 'item_id',
            'name' => 'item_name',
            'perma' => 'item_perma',
            'type' => 'item_type',
            'pic' => 'item_pic'
        ];

        public $id;
        public $name;
        public $type;
        public $perma;
        public $pic;

        /**
         * Constructor
         */
        public function __construct($item = null) {
            parent::__construct($item);
            $this->id = (int) $this->id;
        }

        /**
         * Does a typeahead search on name by item type
         */
        public static function getNameTypeahead($query, $type) {

            $cacheKey = 'MalItem::getTypeahead_' . $query . '_' . $type;
            $retVal = Lib\Cache::Get($cacheKey);

            if (false === $retVal && strlen($query) > 1) {

                $retVal = null;
                $result = Lib\Db::Query('SELECT * FROM mal_items WHERE item_type = :type AND item_name REGEXP(:query) ORDER BY item_name', [ ':query' => '[[:<:]]' . $query, ':type' => $type ]);

                if ($result && $result->count) {

                    $retVal = [];
                    while ($row = Lib\Db::Fetch($result)) {
                        $item = new MalItem($row);
                        $item->sources = $item->getParents();
                        $retVal[] = $item;
                    }

                }

                Lib\Cache::Set($cacheKey, $retVal, 3600);

            }

            return $retVal;

        }

        /**
         * Returns an array of parent items for this object
         */
        public function getParents() {

            $cacheKey = 'MalItem::getItemParents_' . $this->id;
            $retVal = Lib\Cache::Get($cacheKey);

            if (false === $retVal && $this->id) {

                $retVal = null;
                $result = Lib\Db::Query('SELECT i.* FROM mal_xref x INNER JOIN mal_items i ON i.item_id = x.mal_parent WHERE x.mal_child = :id ORDER BY x.mal_parent ASC', [ ':id' => $this->id ]);
                if ($result && $result->count) {

                    $retVal = [];
                    while ($row = Lib\Db::Fetch($result)) {
                        $retVal[] = new MalItem($row);
                    }

                }

                Lib\Cache::Set($retVal, 3600);

            }

            return $retVal;

        }

    }

}