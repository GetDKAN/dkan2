<?php

namespace Drupal\Tests\dkan_datastore\Unit\Manager;

use Drupal\dkan_datastore\Manager\DatastoreManagerBuilderHelper;
use Drupal\dkan_common\Tests\DkanTestBase;
use Dkan\Datastore\Resource;
use Drupal\Core\Entity\EntityRepositoryInterface;

/**
 * @coversDefaultClass Drupal\dkan_datastore\Manager\DatastoreManagerBuilderHelper
 * @group dkan_datastore
 */
class DatastoreManagerBuilderHelperTest extends DkanTestBase {

  /**
   * Tests __construct().
   */
  public function testConstruct() {
    // Setup.
    $mock = $this->getMockBuilder(DatastoreManagerBuilderHelper::class)
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $mockContainer = $this->getMockContainer();

    // Expect
    // Nothing is fetch at construct.
    $mockContainer->expects($this->never())
      ->method('get');

    // Assert.
    $mock->__construct($mockContainer);
    $this->assertSame(
      $mockContainer,
      $this->readAttribute($mock, 'container')
    );
  }

  /**
   * Tests LoadEntityByUuid().
   */
  public function testLoadEntityByUuid() {
    // Setup.
    $mock = $this->getMockBuilder(DatastoreManagerBuilderHelper::class)
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $mockContainer = $this->getMockContainer();
    $this->writeProtectedProperty($mock, 'container', $mockContainer);

    $mockEntityRepository = $this->getMockBuilder(EntityRepositoryInterface::class)
      ->setMethods(['loadEntityByUuid'])
      ->getMockForAbstractClass();

    $mockEntity = $this->createMock(EntityInterface::class);

    $uuid = uniqid('foobar');

    // Expect.
    $mockContainer->expects($this->once())
      ->method('get')
      ->with('entity.repository')
      ->willReturn($mockEntityRepository);

    $mockEntityRepository->expects($this->once())
      ->method('loadEntityByUuid')
      ->with('node', $uuid)
      ->willReturn($mockEntity);

    // Assert.
    $actual = $this->invokeProtectedMethod($mock, 'loadEntityByUuid', $uuid);
    $this->assertSame($mockEntity, $actual);
  }

  /**
   * Tests LoadEntityByUuid() on exception condition.
   */
  public function testLoadEntityByUuidOnException() {
    // Setup.
    $mock = $this->getMockBuilder(DatastoreManagerBuilderHelper::class)
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $mockContainer = $this->getMockContainer();
    $this->writeProtectedProperty($mock, 'container', $mockContainer);

    $mockEntityRepository = $this->getMockBuilder(EntityRepositoryInterface::class)
      ->setMethods(['loadEntityByUuid'])
      ->getMockForAbstractClass();

    $mockEntity = NULL;

    $uuid = uniqid('foobar');

    // Expect.
    $mockContainer->expects($this->once())
      ->method('get')
      ->with('entity.repository')
      ->willReturn($mockEntityRepository);

    $mockEntityRepository->expects($this->once())
      ->method('loadEntityByUuid')
      ->with('node', $uuid)
      ->willReturn($mockEntity);

    $this->setExpectedException(\Exception::class, "Enitity {$uuid} could not be loaded.");

    // Assert.
    $this->invokeProtectedMethod($mock, 'loadEntityByUuid', $uuid);
  }

  /**
   * Tests NewResourceFromEntity().
   */
  public function testNewResourceFromEntity() {

    // Setup.
    $mock = $this->getMockBuilder(DatastoreManagerBuilderHelper::class)
      ->setMethods([
        'loadEntityByUuid',
        'newResourceFromFilePath',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $mockDatasetEntity = $this->getMockBuilder(EntityInterface::class)
      ->setMethods(['id'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $mockResource = $this->createMock(Resource::class);

    $downloadUrl = 'http://foo.bar';

    $datasetValue = (object) [
      'distribution' => [
        (object) [
          'downloadURL' => $downloadUrl,
        ],
      ],
    ];

    $encodedDatasetValue = json_encode($datasetValue);

    $mockDatasetEntity->field_json_metadata = (object) [
      'value' => $encodedDatasetValue,
    ];

    $datasetEntityId = 42;
    $uuid            = uniqid('foo-uuid');

    // Expect.
    $mock->expects($this->once())
      ->method('loadEntityByUuid')
      ->with($uuid)
      ->willReturn($mockDatasetEntity);

    $mockDatasetEntity->expects($this->once())
      ->method('id')
      ->willReturn($datasetEntityId);

    $mock->expects($this->once())
      ->method('newResourceFromFilePath')
      ->with($datasetEntityId, $downloadUrl)
      ->willReturn($mockResource);

    // Assert.
    $actual = $mock->newResourceFromEntity($uuid);
    $this->assertSame($mockResource, $actual);
  }

}
