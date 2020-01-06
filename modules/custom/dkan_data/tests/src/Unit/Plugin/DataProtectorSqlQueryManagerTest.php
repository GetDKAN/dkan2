<?php

namespace Drupal\dkan_data\Tests\Unit;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\dkan_data\Plugin\DataProtectorSqlQueryManager;
use PHPUnit\Framework\TestCase;

class DataProtectorSqlQueryManagerTest extends TestCase {

  public function testDataProtectorSqlQueryManager() {
    $traversable = new \ArrayIterator;
    $cache = new MemoryBackend;
    $module = new ModuleHandler('blah', [], $cache);

    $manager = new DataProtectorSqlQueryManager($traversable, $cache, $module);
    $this->assertTrue(is_object($manager));
  }

}
