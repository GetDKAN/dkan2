<?php

namespace Drupal\Tests\dkan_datastore\Unit\Controller;

use Drupal\Core\File\FileSystem;
use Drupal\dkan_common\Tests\DkanTestBase;
use Drupal\dkan_datastore\Controller\Api;
use Drupal\dkan_datastore\Service\Datastore;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Schema;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\node\NodeInterface;
use Procrastinator\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

define('FILE_CREATE_DIRECTORY', 1);
define('FILE_MODIFY_PERMISSIONS', 2);

/**
 * @coversDefaultClass Drupal\dkan_datastore\Controller\Datastore
 * @group              dkan_datastore
 */
class DatastoreApiTest extends DkanTestBase {

  private $jobStoreData;
  private $tableName;
  private $noNode = FALSE;

  /**
   *
   */
  public function setUp() {
    parent::setUp();
    $this->jobStoreData = (object) [
      'jid' => 1,
      'job_data' => file_get_contents(__DIR__ . '/../../../data/filefetcher.json'),
    ];
  }

  /**
   *
   */
  public function getContainer() {
    // TODO: Change the autogenerated stub.
    $container = $this->getMockBuilder(ContainerInterface::class)
      ->setMethods(['get'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $container->method('get')
      ->with($this->logicalOr($this->equalTo('dkan_datastore.service')))
      ->will($this->returnCallback([$this, 'containerGet']));
    return $container;
  }

  /**
   *
   */
  public function containerGet($serviceName) {
    switch ($serviceName) {
      case 'dkan_datastore.service':
        $mockEntityRepository = $this->mockEntityRepository(EntityRepository::class);
        $mockLogger = $this->createMock(LoggerChannelInterface::class);
        $mockConnection = $this->getConnectionMock();
        $mockQueueFactory = $this->getQueueFactoryMock();
        $mockFileSystem = $this->getFileSystemMock();

        return new Datastore(
          $mockEntityRepository,
          $mockLogger,
          $mockConnection,
          $mockQueueFactory,
          $mockFileSystem
        );
    }
  }

  /**
   *
   */
  private function getQueueFactoryMock() {
    $mock = $this->getMockBuilder(QueueFactory::class)
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMockForAbstractClass();

    $mock->method('get')->willReturn($this->getQueueMock());

    return $mock;

  }

  /**
   *
   */
  private function getQueueMock() {
    $mock = $this->getMockBuilder("\Drupal\Core\Queue\QueueInterface")
      ->disableOriginalConstructor()
      ->setMethods(['createItem'])
      ->getMockForAbstractClass();

    $mock->method('createItem')->willReturn("1");

    return $mock;
  }

  /**
   *
   */
  private function getFileSystemMock() {
    $mock = $this->getMockBuilder(FileSystem::class)
      ->disableOriginalConstructor()
      ->setMethods(['prepareDir'])
      ->getMockForAbstractClass();

    $mock->method('prepareDir')->willReturn(TRUE);

    return $mock;
  }

  /**
   *
   */
  private function getConnectionMock() {
    $mock = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->setMethods(['schema', 'query', 'select', 'insert', 'delete', 'update'])
      ->getMockForAbstractClass();

    $mock->method('schema')->willReturn($this->getSchemaMock());
    $mock->method('query')->willReturn($this->getStatementMock());
    $mock->method('select')->willReturnCallback(function ($tableName) {
      $this->tableName = $tableName;
      return $this->getQueryMock();
    });
    $mock->method('insert')->willReturn($this->getQueryMock());
    $mock->method('delete')->willReturn($this->getQueryMock());
    $mock->method('update')->willReturn($this->getQueryMock());

    return $mock;
  }

  /**
   *
   */
  private function getQueryMock() {
    $mock = $this->getMockBuilder(SelectInterface::class)
      ->disableOriginalConstructor()
      ->setMethods(['fields', 'countQuery', 'condition', 'values', 'execute'])
      ->getMockForAbstractClass();

    $mock->method('fields')->will($this->returnSelf());
    $mock->method('countQuery')->will($this->returnSelf());
    $mock->method('condition')->will($this->returnSelf());
    $mock->method('values')->will($this->returnSelf());
    $mock->method('execute')->willReturn($this->getStatementMock());

    return $mock;
  }

  /**
   *
   */
  private function getStatementMock() {
    $mock = $this->getMockBuilder(StatementInterface::class)
      ->disableOriginalConstructor()
      ->setMethods(['fetchAll', 'fetchField'])
      ->getMockForAbstractClass();
    $mock->method('fetch')->willReturnCallback(function () {
      if ($this->tableName == 'jobstore_filefetcher_filefetcher') {
        return $this->jobStoreData;
      }
      return [];
    });
    $mock->method('fetchAll')
      ->willReturn([
        (object) ['Field' => 'field_1'],
        (object) ['Field' => 'field_2'],
      ]
    );
    $mock->method('fetchField')->willReturn(2);

    return $mock;
  }

  /**
   *
   */
  private function getSchemaMock() {
    $mock = $this->getMockBuilder(Schema::class)
      ->disableOriginalConstructor()
      ->setMethods(['tableExists'])
      ->getMockForAbstractClass();

    $mock->method('tableExists')->willReturn(TRUE);

    return $mock;
  }

  /**
   *
   */
  private function mockEntityRepository() {
    $mock = $this->getMockBuilder(EntityRepository::class)
      ->setMethods(['loadEntityByUuid'])
      ->disableOriginalConstructor()
      ->getMock();

    $node = $this->mockNodeInterface();

    if ($this->noNode) {
      $mock->method('loadEntityByUuid')->willThrowException(new EntityStorageException("You Failed"));
    }
    else {
      $mock->method('loadEntityByUuid')
        ->willReturn($node);
    }

    return $mock;
  }

  /**
   *
   */
  private function mockNodeInterface() {
    $mock = $this->getMockBuilder(NodeInterface::class)
      ->setMethods(['get'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $node = $this->mockFieldItemListInterface();

    $mock->method('get')
      ->willReturn($node);

    return $mock;
  }

  /**
   *
   */
  private function mockFieldItemListInterface() {
    $mock = $this->getMockBuilder(FieldItemListInterface::class)
      ->setMethods(['get'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $list = $this->mockTypedDataInterface();

    $mock->method('get')
      ->willReturn($list);

    return $mock;
  }

  /**
   *
   */
  private function mockTypedDataInterface() {
    $mock = $this->getMockBuilder(TypedDataInterface::class)
      ->setMethods(['getValue'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $data = [
      'value' => json_encode(['data' => ['downloadURL' => __DIR__ . '/../../../data/countries.csv']]),
    ];

    $mock->method('getValue')
      ->willReturn($data);

    return $mock;
  }

  /**
   * Tests Construct().
   */
  public function testSummary() {
    $controller = Api::create($this->getContainer());
    $response = $controller->summary('asdbv');
    $this->assertEquals('{"numOfColumns":2,"columns":["field_1","field_2"],"numOfRows":2}', $response->getContent());
  }

  /**
   *
   */
  public function testImport() {
    $controller = Api::create($this->getContainer());
    $response = json_decode($controller->import('asdbv')->getContent());
    $this->assertEquals($response->FileFetcherResult->status, Result::DONE);
    $this->assertEquals($response->ImporterResult->status, Result::DONE);
  }

  /**
   *
   */
  public function testImportFailure() {
    $this->noNode = TRUE;
    $controller = Api::create($this->getContainer());
    $this->noNode = FALSE;
    $response = $controller->import('asdbv');
    $this->assertEquals('{"message":"You Failed"}', $response->getContent());
  }

  /**
   *
   */
  public function testDelete() {
    $controller = Api::create($this->getContainer());
    $response = $controller->delete('asdbv');
    $this->assertEquals('{"identifier":"asdbv"}', $response->getContent());
  }

  /**
   *
   */
  public function testDeferredImport() {
    $controller = Api::create($this->getContainer());
    $response = $controller->import('asdbv', TRUE);
    $this->assertEquals('{"queueID":"1"}', $response->getContent());
  }

}
