<?php

namespace Api {

  class BracketTest extends \PHPUnit_Framework_TestCase {

    public function testGenerateSmallSeededBracket() {
      $bracketSeed = Bracket::generateSeededBracket(2);
      $this->assertEquals($bracketSeed, [ 1, 2 ], 'bracket seed contains two entries');
    }

    public function testGenerateLargeSeededBracket() {
      $bracketSeed = Bracket::generateSeededBracket(16);
      $expected = [ 1, 16, 8, 9, 4, 13, 5, 12, 2, 15, 7, 10, 3, 14, 6, 11 ];
      $this->assertEquals($bracketSeed, $expected);
    }

  }

}