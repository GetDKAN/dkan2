<?php

namespace Drupal\Tests\dkan_data\Unit;

use Drupal\dkan_data\Modifier;
use PHPUnit\Framework\TestCase;

class ModifierTest extends TestCase {

  public function testModifyGet() {
    $data = (object) ["foo" => "bar"];
    $modifier = new Modifier();
    $this->assertEquals($data, $modifier->modifyGet("baz", $data));
  }

  public function testModifyResources() {
    $resources = [
      (object) ["foo" => "bar"],
      (object) ["foo" => "baz"],
    ];
    $modifier = new Modifier();
    $this->assertEquals($resources, $modifier->modifyResources($resources));
  }

}
