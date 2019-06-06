<?php

namespace Drupal\Tests\dkan_datastore\Unit\Manager;

use Drupal\dkan_datastore\Manager\DatastoreManagerBuilderHelper;
use Drupal\dkan_common\Tests\DkanTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Dkan\Datastore\Manager\IManager;
use Dkan\Datastore\Resource;
use Dkan\Datastore\Manager\Info;
use Dkan\Datastore\Manager\InfoProvider;
use Dkan\Datastore\Manager\SimpleImport\SimpleImport;
use Dkan\Datastore\LockableBinStorage;
use Dkan\Datastore\Manager\Factory as DatastoreManagerFactory;
use Dkan\Datastore\Locker;
use Drupal\dkan_datastore\Storage\Database as DatastoreDatabase;
use Dkan\Datastore\Storage\IKeyValue;
use Drupal\Core\Entity\EntityInterface;
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
    // setup
    $mock = $this->getMockBuilder(DatastoreManagerBuilderHelper::class)
      ->setMethods(null)
      ->disableOriginalConstructor()
      ->getMock();

    $mockContainer = $this->getMockContainer();

    // expect
    // nothing is fetch at construct
    $mockContainer->expects($this->never())
      ->method('get');

    // assert
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
    // setup
    $mock = $this->getMockBuilder(DatastoreManagerBuilderHelper::class)
      ->setMethods(null)
      ->disableOriginalConstructor()
      ->getMock();

    $mockContainer = $this->getMockContainer();
    $this->writeProtectedProperty($mock, 'container', $mockContainer);

    $mockEntityRepository = $this->getMockBuilder(EntityRepositoryInterface::class)
      ->setMethods(['loadEntityByUuid'])
      ->getMockForAbstractClass();

    $mockEntity = $this->createMock(EntityInterface::class);

    $uuid = uniqid('foobar');

    // expect
    $mockContainer->expects($this->once())
      ->method('get')
      ->with('entity.repository')
      ->willReturn($mockEntityRepository);

    $mockEntityRepository->expects($this->once())
      ->method('loadEntityByUuid')
      ->with('node', $uuid)
      ->willReturn($mockEntity);

    // assert
    $actual = $this->invokeProtectedMethod($mock, 'loadEntityByUuid', $uuid);
    $this->assertSame($mockEntity, $actual);
  }

  /**
   * Tests LoadEntityByUuid() on exception condition.
   */
  public function testLoadEntityByUuidOnException() {
    // setup
    $mock = $this->getMockBuilder(DatastoreManagerBuilderHelper::class)
      ->setMethods(null)
      ->disableOriginalConstructor()
      ->getMock();

    $mockContainer = $this->getMockContainer();
    $this->writeProtectedProperty($mock, 'container', $mockContainer);

    $mockEntityRepository = $this->getMockBuilder(EntityRepositoryInterface::class)
      ->setMethods(['loadEntityByUuid'])
      ->getMockForAbstractClass();

    $mockEntity = null;

    $uuid = uniqid('foobar');

    // expect
    $mockContainer->expects($this->once())
      ->method('get')
      ->with('entity.repository')
      ->willReturn($mockEntityRepository);

    $mockEntityRepository->expects($this->once())
      ->method('loadEntityByUuid')
      ->with('node', $uuid)
      ->willReturn($mockEntity);

    $this->setExpectedException(\Exception::class, "Enitity {$uuid} could not be loaded.");

    // assert
    $this->invokeProtectedMethod($mock, 'loadEntityByUuid', $uuid);
  }

  /**
   * Tests NewResourceFromEntity().
 */

  public function testNewResourceFromEntity() {

    // setup
    $mock = $this->getMockBuilder(DatastoreManagerBuilderHelper::class)
      ->setMethods([
        'loadEntityByUuid',
        'newResourceFromFilePath',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $mockDatasetEntity = $this->getMockBuilder(EntityInterface::class)
      ->setMethods([
        'id'
      ])
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

    // expect
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

    // assert
    $actual = $mock->newResourceFromEntity($uuid);
    $this->assertSame($mockResource, $actual);

  }
}
