<?php

namespace Api {

  use Lib;

  class Typeahead extends Lib\Dal {

    protected $_dbTable = 'typeahead';
    protected $_dbPrimaryKey = 'id';
    protected $_dbMap = [
      'id' => 'typeahead_id',
      'name' => 'typeahead_name',
      'category' => 'typeahead_category',
      'image' => 'typeahead_image'
    ];

    public $id;
    public $name;
    public $category;
    public $image;

  }

}