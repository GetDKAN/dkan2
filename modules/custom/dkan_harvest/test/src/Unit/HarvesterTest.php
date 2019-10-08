<?php

use Harvest\Harvester;
use Drupal\dkan_harvest\Harvester;
use Drupal\dkan_common\Tests\Mock\Chain;
use Drupal\dkan_harvest\Storage\FileFactory;
use PHPUnit\Framework\TestCase;
use Drupal\dkan_harvest\Storage\File;

/**
 *
 */
class HarvesterTest extends TestCase {

  /**
   *
   */
  public function testGetHarvestPlan() {
    $storeFactory = (new Chain($this))
      ->add(FileFactory::class, "getInstance", File::class)
      ->add(File::class, "retrieve", "Hello")
      ->getMock();

    $service = new Harvester($storeFactory);
    $plan = $service->getHarvestPlan("test");
    $this->assertEquals("Hello", $plan);
  }

  /**
   *
   */
  public function testDeregisterHarvest() {
    $storeFactory = (new Chain($this))
      ->add(FileFactory::class, "getInstance", File::class)
      ->add(File::class, "retrieve", "Hello")
      ->add(File::class, "remove", "Hello")
      ->getMock();

    $dkanHarvester = (new Chain($this))
      ->add(Harvester::class, "revert", NULL)
      ->getMock();

    $service = $this->getMockBuilder(Harvester::class)
      ->setConstructorArgs([$storeFactory])
      ->setMethods(['getDkanHarvesterInstance'])
      ->getMock();

    $service->method('getDkanHarvesterInstance')->willReturn($dkanHarvester);

    $result = $service->deregisterHarvest("test");
    $this->assertEquals("Hello", $result);
  }

  /**
   *
   */
  public function testRunHarvest() {
    $storeFactory = (new Chain($this))
      ->add(FileFactory::class, "getInstance", File::class)
      ->add(File::class, "retrieve", "Hello")
      ->getMock();

    $dkanHarvester = (new Chain($this))
      ->add(Harvester::class, "harvest", "Hello")
      ->getMock();

    $service = $this->getMockBuilder(Harvester::class)
      ->setConstructorArgs([$storeFactory])
      ->setMethods(['getDkanHarvesterInstance'])
      ->getMock();

    $service->method('getDkanHarvesterInstance')->willReturn($dkanHarvester);

    $result = $service->runHarvest("test");
    $this->assertEquals("Hello", $result);
  }

  /**
   *
   */
  public function testGetAllHarvestRunInfo() {
    $storeFactory = (new Chain($this))
      ->add(FileFactory::class, "getInstance", File::class)
      ->add(File::class, "retrieve", "Hello")
      ->getMock();

    $dkanHarvester = (new Chain($this))
      ->add(Harvester::class, "harvest", "Hello")
      ->getMock();

    $service = $this->getMockBuilder(Harvester::class)
      ->setConstructorArgs([$storeFactory])
      ->setMethods(['getDkanHarvesterInstance'])
      ->getMock();

    $service->method('getDkanHarvesterInstance')->willReturn($dkanHarvester);

    $result = $service->getAllHarvestRunInfo("test");
    $this->assertTrue(is_array($result));
  }

  /**
   *
   */
  public function testGetHarvestRunInfo() {
    $storeFactory = (new Chain($this))
      ->add(FileFactory::class, "getInstance", File::class)
      ->add(File::class, "retrieve", "Hello")
      ->getMock();

    $dkanHarvester = (new Chain($this))
      ->add(Harvester::class, "harvest", "Hello")
      ->getMock();

    $service = $this->getMockBuilder(Harvester::class)
      ->setConstructorArgs([$storeFactory])
      ->setMethods(['getDkanHarvesterInstance'])
      ->getMock();

    $service->method('getDkanHarvesterInstance')->willReturn($dkanHarvester);

    $result = $service->getHarvestRunInfo("test", "1");
    $this->assertFalse($result);
  }

}
