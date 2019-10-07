<?php

use PHPUnit\Framework\TestCase;
use FileFetcher\FileFetcher;
use Procrastinator\Result;
use Dkan\Datastore\Resource;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\Plugin\DataType\FieldItem;
use Drupal\node\Entity\Node;
use Drupal\dkan_common\Tests\Mock\Chain;
use Drupal\dkan_datastore\Service\Resource as Service;
use Drupal\dkan_datastore\Storage\JobStore;

class ResourceTest extends TestCase
{
  public function testNoFileFetcher() {

    $object = (object) [];
    $object->data = (object) [];
    $object->data->downloadURL = "http://google.com";

    $meta = [
      "value" => json_encode($object)
    ];

    $entityRepository = (new Chain($this))
      ->add(EntityRepository::class, "loadEntityByUuid", Node::class)
      ->add(Node::class, "id", "1")
      ->add(Node::class, "get", FieldItemList::class)
      ->add(FieldItemList::class, "get", FieldItem::class)
      ->add(FieldItem::class, "getValue", $meta)
      ->getMock();

    $fileSystem = (new Chain($this))
      ->add(FileSystem::class, "chmod", null)
      ->getMock();

    $jobStore = (new Chain($this))
      ->add(JobStore::class, "store", null)
      ->getMock();

    $service = new Service("123", $entityRepository, $fileSystem, $jobStore);
    $resource = $service->get();

    $this->assertTrue($resource instanceof Resource);
  }

  public function testFileFetcherNotDone() {

    $object = (object) [];
    $object->data = (object) [];
    $object->data->downloadURL = "http://google.com";

    $meta = [
      "value" => json_encode($object)
    ];

    $entityRepository = (new Chain($this))
      ->add(EntityRepository::class, "loadEntityByUuid", Node::class)
      ->add(Node::class, "id", "1")
      ->add(Node::class, "get", FieldItemList::class)
      ->add(FieldItemList::class, "get", FieldItem::class)
      ->add(FieldItem::class, "getValue", $meta)
      ->getMock();

    $fileSystem = (new Chain($this))
      ->add(FileSystem::class, "chmod", null)
      ->getMock();

    $data = json_encode((object) [
      "destination" => "hello"
    ]);

    $jobStore = (new Chain($this))
      ->add(JobStore::class, "retrieve", FileFetcher::class)
      ->add(FileFetcher::class, "getResult", Result::class)
      ->add(Result::class, "getData", $data)
      ->getMock();

    $service = new Service("123", $entityRepository, $fileSystem, $jobStore);
    $resource = $service->get(true);

    $this->assertTrue(!isset($resource));
  }

  public function testFileFetcherDone() {

    $object = (object) [];
    $object->data = (object) [];
    $object->data->downloadURL = "http://google.com";

    $meta = [
      "value" => json_encode($object)
    ];

    $entityRepository = (new Chain($this))
      ->add(EntityRepository::class, "loadEntityByUuid", Node::class)
      ->add(Node::class, "id", "1")
      ->add(Node::class, "get", FieldItemList::class)
      ->add(FieldItemList::class, "get", FieldItem::class)
      ->add(FieldItem::class, "getValue", $meta)
      ->getMock();

    $fileSystem = (new Chain($this))
      ->add(FileSystem::class, "chmod", null)
      ->getMock();

    $data = json_encode((object) [
      "destination" => "hello"
    ]);

    $jobStore = (new Chain($this))
      ->add(JobStore::class, "retrieve", FileFetcher::class)
      ->add(FileFetcher::class, "getResult", Result::class)
      ->add(Result::class, "getData", $data)
      ->add(Result::class, "getStatus", Result::DONE)
      ->getMock();

    $service = new Service("123", $entityRepository, $fileSystem, $jobStore);
    $resource = $service->get(true);

    $this->assertTrue($resource instanceof Resource);
    $this->assertTrue($service->getResult() instanceof Result);
  }

  public function testNoStoredFileFetcher() {

    $object = (object) [];
    $object->data = (object) [];
    $object->data->downloadURL = "http://google.com";

    $meta = [
      "value" => json_encode($object)
    ];

    $entityRepository = (new Chain($this))
      ->add(EntityRepository::class, "loadEntityByUuid", Node::class)
      ->add(Node::class, "id", "1")
      ->add(Node::class, "get", FieldItemList::class)
      ->add(FieldItemList::class, "get", FieldItem::class)
      ->add(FieldItem::class, "getValue", $meta)
      ->getMock();

    $fileSystem = (new Chain($this))
      ->add(FileSystem::class, "realpath", "/")
      ->add(FileSystem::class, "prepareDirectory", null)
      ->getMock();

    $jobStore = (new Chain($this))
      ->add(JobStore::class, "retrieve", null)
      ->add(JobStore::class, "store", null)
      ->getMock();

    $data = json_encode((object) [
      "destination" => "hello"
    ]);
    $fileFetcher = (new Chain($this))
      ->add(FileFetcher::class, "getResult", Result::class)
      ->add(Result::class, "getData", $data)
      ->add(Result::class, "getStatus", Result::DONE)
      ->getMock();

    $builder = $this->getMockBuilder(Service::class);
    $builder->setConstructorArgs(["123", $entityRepository, $fileSystem, $jobStore])
      ->setMethods(["getFileFetcherInstance"]);

    $service = $builder->getMock();
    $service->method("getFileFetcherInstance")->willReturn($fileFetcher);

    $resource = $service->get(true);

    $this->assertTrue($resource instanceof Resource);
  }

}
