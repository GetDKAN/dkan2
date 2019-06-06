<?php

namespace Drupal\dkan_datastore\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Dkan\Datastore\Resource;
use Dkan\Datastore\Manager\Info;
use Dkan\Datastore\Manager\InfoProvider;
use Dkan\Datastore\LockableBinStorage;
use Dkan\Datastore\Manager\Factory as DatastoreManagerFactory;
use Dkan\Datastore\Locker;
use Drupal\dkan_datastore\Storage\Database as DatastoreDatabase;
use Dkan\Datastore\Storage\IKeyValue;
use Drupal\Core\Entity\EntityInterface;

/**
 * Factory class to instantiate classes that are needed to build the manager.
 *
 * Those classes exist outside of service container.
 *
 * @TODO may need a refactor in the future if dependencies are moved to service container.
 */
class DatastoreManagerBuilderHelper {

  protected $container;

  /**
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   *
   * @param string $binName
   * @param \Dkan\Datastore\Locker $locker
   * @param \Dkan\Datastore\Storage\IKeyValue $keyValueStore
   * @return \Dkan\Datastore\LockableBinStorage
   */
  public function newLockableStorage(string $binName, Locker $locker, IKeyValue $keyValueStore): LockableBinStorage {
    return new LockableBinStorage($binName, $locker, $keyValueStore);
  }

  /**
   *
   * @param string $name
   * @return \Dkan\Datastore\Locker
   */
  public function newLocker(string $name): Locker {
    return new Locker($name);
  }

  /**
   *
   * @param mixed $id
   * @param string $filePath
   * @return \Dkan\Datastore\Resource
   */
  public function newResourceFromFilePath($id, $filePath): Resource {
    return new Resource($id, $filePath);
  }

  /**
   *
   * @return \Dkan\Datastore\Manager\InfoProvider
   */
  public function newInfoProvider(): InfoProvider {
    return new InfoProvider();
  }

  /**
   *
   * @param string $class
   * @param string $machineName
   * @param string $label
   * @return \Dkan\Datastore\Manager\Info
   */
  public function newInfo(string $class, string $machineName, string $label): Info {
    return new Info($class, $machineName, $label);
  }

  /**
   * Gets the Manager Factory.
   *
   * @param \Dkan\Datastore\Resource $resource
   * @param \Dkan\Datastore\Manager\InfoProvider $provider
   * @param \Dkan\Datastore\LockableBinStorage $bin_storage
   * @param \Drupal\dkan_datastore\Storage\Database $database
   *
   * @return \Dkan\Datastore\Manager\Factory
   */
  public function newDatastoreFactory(
    Resource $resource,
    InfoProvider $provider,
    LockableBinStorage $bin_storage,
    DatastoreDatabase $database
  ) {
    return new DatastoreManagerFactory($resource, $provider, $bin_storage, $database);
  }

  /**
   *
   * @param string $uuid
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function loadEntityByUuid(string $uuid): EntityInterface {
    $entity = $this->container
      ->get('entity.repository')
      ->loadEntityByUuid('node', $uuid);

    if (!($entity instanceof EntityInterface)) {
      throw new \Exception("Enitity {$uuid} could not be loaded.");
    }

    return $entity;
  }

  /**
   *
   */
  public function newResourceFromEntity($uuid) {
    $dataset = $this->loadEntityByUuid($uuid);
    $metadata = json_decode($dataset->field_json_metadata->value);

    return $this->newResourceFromFilePath(
        $dataset->id(),
        $metadata->distribution[0]->downloadURL
    );
  }

}
