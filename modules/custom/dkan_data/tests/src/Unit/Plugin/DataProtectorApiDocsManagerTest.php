<?php

namespace Drupal\dkan_data\Tests\Unit;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\dkan_data\Plugin\DataProtectorApiDocsManager;
use PHPUnit\Framework\TestCase;

class DataProtectorApiDocsManagerTest extends TestCase {

  public function testDataProtectorApiDocsManager() {
    $traversable = new \ArrayIterator;
    $cache = new MemoryBackend;
    $module = new ModuleHandler('blah', [], $cache);

    $manager = new DataProtectorApiDocsManager($traversable, $cache, $module);
    $this->assertTrue(is_object($manager));
  }

}
