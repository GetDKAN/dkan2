<?php

namespace Drupal\Tests\dkan_api\Unit\Storage;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dkan_common\Tests\DkanTestBase;
use Drupal\dkan_api\Storage\DrupalNodeDataset;
use Drupal\node\NodeStorageInterface;
use Drupal\dkan_api\Storage\ThemeValueReferencer;
use Drupal\dkan_datastore\Manager\DatastoreManagerBuilderHelper;
use Drupal\dkan_datastore\Manager\DeferredImportQueuer;
use Dkan\Datastore\Resource;

/**
 * Tests Drupal\dkan_api\Storage\DrupalNodeDataset.
 *
 * @coversDefaultClass \Drupal\dkan_api\Storage\DrupalNodeDataset
 * @group dkan_api
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
class DrupalNodeDatasetTest extends DkanTestBase {

  /**
   * Tests __construct().
   */
  public function testConstruct() {
    $mockEntityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $mockThemeValueReferencer = $this->createMock(ThemeValueReferencer::class);
    $mock = $this->getMockBuilder(DrupalNodeDataset::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Assert.
    $mock->__construct($mockEntityTypeManager, $mockThemeValueReferencer);

    $this->assertSame(
            $mockEntityTypeManager,
            $this->readAttribute($mock, 'entityTypeManager')
    );
    $this->assertSame(
            $mockThemeValueReferencer,
            $this->readAttribute($mock, 'themeValueReferencer')
    );
  }

  /**
   * Tests getNodeStorage.
   */
  public function testGetNodeStorage() {

    // Setup.
    $mock = $this->getMockBuilder(DrupalNodeDataset::class)
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $mockEntityTypeManager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->setMethods(['getStorage'])
      ->getMockForAbstractClass();

    $mockNodeStorage = $this->createMock(NodeStorageInterface::class);

    $this->writeProtectedProperty($mock, 'entityTypeManager', $mockEntityTypeManager);

    // Expect.
    $mockEntityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($mockNodeStorage);

    // Assert.
    $actual = $this->invokeProtectedMethod($mock, 'getNodeStorage');

    $this->assertSame($mockNodeStorage, $actual);
  }

  /**
   * Tests getType().
   */
  public function testGetType() {

    // Setup.
    $mock = $this->getMockBuilder(DrupalNodeDataset::class)
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $expected = 'data';

    // Assert.
    $actual = $this->invokeProtectedMethod($mock, 'getType');

    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests EnqueueDeferredImport().
   */
  public function testEnqueueDeferredImport() {
    // setup
    $mock = $this->getMockBuilder(DrupalNodeDataset::class)
      ->setMethods(null)
      ->disableOriginalConstructor()
      ->getMock();

    $mockBuilderHelper = $this->getMockBuilder(DatastoreManagerBuilderHelper::class)
      ->setMethods(['newResourceFromEntity'])
      ->disableOriginalConstructor()
      ->getMock();

    $mockDeferredImporter = $this->getMockBuilder(DeferredImportQueuer::class)
      ->setMethods(['createDeferredResourceImport'])
      ->disableOriginalConstructor()
      ->getMock();
    $this->setActualContainer([
      'dkan_datastore.manager.datastore_manager_builder' => $mockBuilderHelper,
      'dkan_datastore.manager.deferred_import_queuer'    => $mockDeferredImporter,
    ]);

    $mockResource = $this->createMock(Resource::class);
    $uuid         = uniqid('foo');
    $expected     = 42;

    // expect
    $mockBuilderHelper->expects($this->once())
      ->method('newResourceFromEntity')
      ->with($uuid)
      ->willReturn($mockResource);

    $mockDeferredImporter->expects($this->once())
      ->method('createDeferredResourceImport')
      ->with($uuid, $mockResource)
      ->willReturn($expected);

    // assert
    $actual = $this->invokeProtectedMethod($mock, 'enqueueDeferredImport', $uuid);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Placeholder.
   */
  public function testRemainingMethods() {

    $this->markTestIncomplete('Review of other methods in ' . DrupalNodeDataset::class . ' pending reivew of refactor.');
  }

}
