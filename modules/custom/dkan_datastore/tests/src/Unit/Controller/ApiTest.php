<?php

namespace Drupal\Tests\dkan_datastore\Unit\Controller;

use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\Statement;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\Plugin\DataType\FieldItem;
use Drupal\Core\File\FileSystem;
use Drupal\dkan_common\Tests\DkanTestBase;
use Drupal\dkan_common\Tests\Mock\Sequence;
use Drupal\dkan_datastore\Controller\Api;
use Drupal\dkan_datastore\Service\Datastore;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Schema;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\dkan_datastore\Unit\Mock\Container;
use Procrastinator\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dkan_common\Tests\Mock\Chain;
use Drupal\dkan_common\Tests\Mock\Options;

define('FILE_CREATE_DIRECTORY', 1);
define('FILE_MODIFY_PERMISSIONS', 2);

/**
 * @coversDefaultClass \Drupal\dkan_datastore\Controller\Api
 * @group dkan_datastore
 */
class DatastoreApiTest extends DkanTestBase {

  /**
   *
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   *
   */
  public function testSummary() {
    $controller = Api::create($this->getContainer()->get());
    $response = $controller->summary('asdbv');
    $this->assertEquals('{"numOfColumns":2,"columns":["field_1","field_2"],"numOfRows":2}', $response->getContent());
  }

  /**
   *
   */
  public function testImport() {
    $controller = Api::create($this->getMockChain()->getMock());
    $response = $controller->import('1');
    $body = json_decode($response->getContent());
    $this->assertEquals($body->FileFetcherResult->status, Result::DONE);
    $this->assertEquals($body->ImporterResult->status, Result::DONE);
  }

  /**
   *
   */
  public function testImportFailure() {
    $container = $this->getContainer();
    $container->setNoNode();

    $controller = Api::create($container->get());

    $response = $controller->import('asdbv');
    $this->assertEquals('{"message":"You Failed"}', $response->getContent());
  }

  /**
   *
   */
  public function testDelete() {
    $controller = Api::create($this->getContainer()->get());
    $response = $controller->delete('asdbv');
    $this->assertEquals('{"identifier":"asdbv","message":"The datastore for resource asdbv was succesfully dropped."}', $response->getContent());
  }

  /**
   *
   */
  public function testDeferredImport() {
    $controller = Api::create($this->getContainer()->get());
    $response = $controller->import('asdbv', TRUE);
    $this->assertEquals('{"message":"Resource asdbv has been queued to be imported.","queue_id":"1"}', $response->getContent());
  }

  /**
   *
   */
  private function getMockChain() {

    $resourceFile = [
      'value' => json_encode(['data' => ['downloadURL' => __DIR__ . '/../../../data/countries.csv']]),
    ];

    $fileFetcherJob = (object) [
      'jid' => 1,
      'job_data' => file_get_contents(__DIR__ . '/../../../data/filefetcher.json'),
    ];

    $containerOptions = (new Options())
      ->add('entity.repository', EntityRepository::class)
      ->add('database', Connection::class)
      ->add('queue', QueueFactory::class)
      ->add('file_system', FileSystem::class);

    $selectOptions = (new Options())
      ->add('jobstore_filefetcher_filefetcher', (new Sequence())->add(FALSE)->add($fileFetcherJob))
      ->add('jobstore_dkan_datastore_importer', FALSE)
      ->use('select_1');

    /* @var $mockChain2 \Drupal\dkan_common\Tests\Mock\Chain */
    $mockChain2 = (new Chain($this))
      ->add(ContainerInterface::class, 'get', $containerOptions)

      ->add(QueueFactory::class, 'get', QueueInterface::class)

      ->add(Statement::class, 'fetch', $selectOptions)
      ->add(Statement::class, 'fetchAll', [
        (object) ['Field' => "country"],
        (object) ['Field' => "population"],
        (object) ['Field' => "id"],
        (object) ['Field' => "timestamp"],
      ])

      ->add(Connection::class, 'schema', Schema::class)
      ->add(Connection::class, 'select', Select::class, 'select_1')
      ->add(Select::class, 'fields', Select::class)
      ->add(Select::class, 'condition', Select::class)
      ->add(Select::class, 'execute', Statement::class)

      ->add(Connection::class, 'insert', Insert::class)
      ->add(Insert::class, 'fields', Insert::class)
      ->add(Insert::class, 'values', Insert::class)
      ->add(Insert::class, 'execute', NULL)

      ->add(Connection::class, 'update', Update::class)
      ->add(Update::class, 'fields', Update::class)
      ->add(Update::class, 'condition', Update::class)
      ->add(Update::class, 'execute', NULL)

      ->add(Connection::class, 'query', Statement::class)

      ->add(Schema::class, 'createTable', NULL)
      ->add(Schema::class, 'tableExists', TRUE)

      ->add(EntityRepository::class, 'loadEntityByUuid', Node::class)
      ->add(Node::class, 'get', FieldItemList::class)
      ->add(Node::class, 'id', "1")
      ->add(FieldItemList::class, 'get', FieldItem::class)
      ->add(FieldItem::class, 'getValue', $resourceFile)
      ->add(FileSystem::class, 'realpath', '/tmp')
      ->add(FileSystem::class, 'prepareDirectory', NULL);

    $mockChain = (new Chain($this))
      ->add(ContainerInterface::class, 'get', Datastore::create($mockChain2->getMock()));

    return $mockChain;
  }

  /**
   *
   */
  private function getContainer() {
    return new Container($this);
  }

}
