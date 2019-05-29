<?php

namespace Drupal\dkan_datastore\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Dkan\Datastore\Manager\IManager;
use Dkan\Datastore\Resource;
use Dkan\Datastore\Manager\Info;
use Dkan\Datastore\Manager\InfoProvider;
use Dkan\Datastore\Manager\SimpleImport\SimpleImport;
use Dkan\Datastore\LockableBinStorage;
use Dkan\Datastore\Manager\Factory as DatastoreManagerFactory;
use Dkan\Datastore\Locker;

/**
 * DatastoreManagerBuilder.
 *
 * This is a single use builder class to make.
 */
class DatastoreManagerBuilder {

  protected $container;

  /**
   *
   * @var \Dkan\Datastore\Resource
   */
  protected $resource;

  /**
   *
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * @todo make it so Info is overridable. seems like a good place to specify a different import handler.
   * @return \Dkan\Datastore\Manager\Info
   */
  protected function getInfo() {
    return new Info(SimpleImport::class, "simple_import", "SimpleImport");
  }

  /**
   *
   */
  protected function getInfoProvider() {
    $infoProvider = new \Dkan\Datastore\Manager\InfoProvider();
    $infoProvider->addInfo($this->getInfo());
    return $infoProvider;
  }

  /**
   *
   * @param string $uuid
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function loadEntityByUuid(string $uuid) {
    return $this->container
      ->get('entity.repository')
      ->loadEntityByUuid('node', $uuid);
  }

  /**
   *
   */
  public function setResource($id, $filePath) {
    $this->resource = new Resource($id, $filePath);
    return $this;
  }

  /**
   *
   */
  protected function getResource() {
    return $this->resource;
  }

  /**
   *
   */
  public function getLockableStorage() {
    return new LockableBinStorage(
      "dkan_datastore",
      new Locker("dkan_datastore"),
      $this->container->get('dkan_datastore.storage.variable')
    );
  }

  /**
   *
   */
  protected function getFactory($resource, $provider, $bin_storage, $database) {
    return new DatastoreManagerFactory($resource, $provider, $bin_storage, $database);
  }

  /**
   * @return \Drupal\dkan_datastore\Storage\Database
   */
  protected function getDatabase() {
    return $this->container
      ->get('dkan_datastore.database');
  }

  /**
   * Build datastore manager with set params, otherwise defaults.
   *
   * @param string $uuid
   *
   * @return \Dkan\Datastore\Manager\IManager
   */
  public function build(string $uuid): IManager {

    $resource = $this->getResource();

    if (!($resource instanceof Resource)) {
      $dataset = $this->loadEntityByUuid($uuid);
      $metadata = json_decode($dataset->field_json_metadata->value);
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
