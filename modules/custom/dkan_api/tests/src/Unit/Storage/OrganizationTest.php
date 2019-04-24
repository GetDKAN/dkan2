<?php

namespace Drupal\Tests\dkan_api\Unit\Storage;

use Dkan\Tests\DkanTestBase;
use Drupal\dkan_api\Storage\Organization;
use Drupal\dkan_api\Storage\DrupalNodeDataset;

/**
 *
 * @coversDefaultClass \Drupal\dkan_api\Storage\Organization
 * @group dkan_api
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
class OrganizationTest extends DkanTestBase {

  /**
   * Tests __construct().
   */
  public function testConstruct() {
    $mockDataSetStorage = $this->createMock(DrupalNodeDataset::class);

    $mock = $this->getMockBuilder(Organization::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Assert.
    $mock->__construct($mockDataSetStorage);

    $this->assertSame(
            $mockDataSetStorage,
            $this->readAttribute($mock, 'datasetStorage')
    );
  }

  /**
   *
   */
  public function testRemainingMethods() {

    $this->markTestIncomplete('Review of other methods in ' . DrupalNodeDataset::class . ' pending reivew of refactor.');
  }

}
