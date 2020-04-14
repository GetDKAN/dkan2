<?php

use MockChain\Chain;
use MockChain\Options;
use PHPUnit\Framework\TestCase;

class DkanSearchServiceTest extends TestCase {

  public function testSearchByIndexField() {
    $result = (new \Drupal\dkan_search\Service())
      ->searchByIndexField("theme", "finance");
    $this->assertEquals($result, [1, 2]);
  }

}
