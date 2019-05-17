<?php

namespace Drupal\dkan_datastore\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Dkan\Datastore\Manager\IManager;
use Dkan\Datastore\Resource;
use Dkan\Datastore\Manager\InfoProvider;
use Dkan\Datastore\Manager\Info;
use Dkan\Datastore\Manager\SimpleImport\SimpleImport;
use Dkan\Datastore\LockableBinStorage;
use Dkan\Datastore\Manager\Factory as DatastoreManagerFactory;
use Dkan\Datastore\Locker;

/**
 * DatastoreManagerBuilder.
 *
 * This is a single use builder class to make
 */
class DatastoreManagerBuilder {

  protected $container;

  /**
   *
   * @var Info
   */
  protected $info;

  /**
   *
   * @var Resource
   */
  protected $resource;

  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * Set information about the manager class to build.
   *
   * This allows overriding the importer class amongst other things.
   * 
   * @param type $class
   * @param type $machine_name
   * @param type $label
   * @return $this
   */
  public function setInfo($class, $machine_name, $label) {
    $this->info = new Info($class, $machine_name, $label);
    return $this;
  }

  protected function getInfo() {

    if (!isset($this->info)) {
      // create one with default values.
      $this->info = new Info(SimpleImport::class, "simple_import", "SimpleImport");
    }

    return $this->info;
  }

  protected function getInfoProvider() {
    return new InfoProvider($this->getInfo());
  }

  protected function loadEntityByUuid(string $uuid): \stdClass {
    return $this->container
        ->get('entity.repository')
        ->loadEntityByUuid('node', $uuid);
  }

  public function setResource($id, $filePath) {
    $this->resource = new Resource($id, $filePath);
    return $this;
  }

  protected function getResource() {
    return $this->resource;
  }

  public function getLockableStorage() {
    return new LockableBinStorage(
      "dkan_datastore",
      new Locker("dkan_datastore"),
      $this->container->get('dkan_datastore.storage.variable')
    );
  }

  protected function getFactory($resource, $provider, $bin_storage, $database) {
    return new DatastoreManagerFactory($resource, $provider, $bin_storage, $database);
  }

  protected function getDatabase() {
    return $this->container
        ->get('dkan_datastore.database');
  }

  /**
   * Build datastore manager with set params, otherwise defaults.
   *
   * @param string $uuid
   * @return IManager
   */
  public function build(string $uuid): IManager {

    $dataset = $this->loadEntityByUuid($uuid);

    $metadata = json_decode($dataset->field_json_metadata->value);

    $resource = $this->getResource();

    if (!($resource instanceof Resource)) {
      $resource = $this->setResource(
          $dataset->id(),
          $metadata->distribution[0]->downloadURL
        )
        ->getResource();
    }

    $provider    = $this->getInfoProvider();
    $bin_storage = $this->getLockableStorage();
    $database    = $this->getDatabase();

    $factory = $this->getFactory($resource, $provider, $bin_storage, $database);

    return $factory->get();
  }

}
