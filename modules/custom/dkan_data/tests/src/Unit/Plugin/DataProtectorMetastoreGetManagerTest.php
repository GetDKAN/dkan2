<?php

namespace Drupal\dkan_data\Tests\Unit;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\dkan_data\Plugin\DataProtectorMetastoreGetManager;
use PHPUnit\Framework\TestCase;

class DataProtectorMetastoreGetManagerTest extends TestCase {

  public function testDataProtectorMetastoreGetManager() {
    $traversable = new \ArrayIterator;
    $cache = new MemoryBackend;
    $module = new ModuleHandler('blah', [], $cache);

    $manager = new DataProtectorMetastoreGetManager($traversable, $cache, $module);
    $this->assertTrue(is_object($manager));
  }

}
