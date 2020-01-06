<?php

namespace Drupal\dkan_data\Tests\Unit;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\dkan_data\Plugin\DataProtectorManager;
use PHPUnit\Framework\TestCase;

class DataProtectorManagerTest extends TestCase {

  public function testDataProtectorManager() {
    $traversable = new \ArrayIterator;
    $cache = new MemoryBackend;
    $module = new ModuleHandler('blah', [], $cache);

    $manager = new DataProtectorManager($traversable, $cache, $module);
    $this->assertTrue(is_object($manager));
  }

}
