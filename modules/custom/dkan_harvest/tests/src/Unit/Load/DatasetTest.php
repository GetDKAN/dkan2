<?php

namespace Drupal\Tests\dkan_harvest\Unit\Extract;

use Drupal\dkan_common\Tests\DkanTestBase;
use Drupal\dkan_harvest\Load\Dataset;
use Harvest\Storage\Storage;
use Sae\Sae;

/**
 * Tests Drupal\dkan_harvest\Load\Dataset.
 *
 * @coversDefaultClass Drupal\dkan_harvest\Load\Dataset
 * @group dkan_harvest
 */
class DatasetTest extends DkanTestBase {

  /**
   * Tests saveItem().
   */
  public function testSaveItem() {
    // setup
    $mock = $this->getMockBuilder(Dataset::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDatasetEngine'])
            ->getMock();

    $mockEngine = $this->getMockBuilder(Sae::class)
            ->setMethods(['post'])
            ->disableOriginalConstructor()
            ->getMock();
    
    $item = (object) ['foo' => 'bar'];
    // expect
    $mock->expects($this->once())
            ->method('getDatasetEngine')
            ->willReturn($mockEngine);

    $mockEngine->expects($this->once())
            ->method('post')
            ->willReturn(json_encode($item));

    // assert
    $this->invokeProtectedMethod($mock, 'saveItem', $item);
  }

  public function testGetDatasetEngine() {

    $this->markTestSkipped("Need to refactor to use DI");
  }

}
