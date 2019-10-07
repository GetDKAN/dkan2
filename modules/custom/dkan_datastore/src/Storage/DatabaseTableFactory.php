<?php

namespace Drupal\dkan_datastore\Storage;

use Contracts\FactoryInterface;
use Dkan\Datastore\Resource;
use Drupal\Core\Database\Connection;

/**
 *
 */
class DatabaseTableFactory implements FactoryInterface {
  private $connection;
  private $databaseTables;

  /**
   *
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
    $this->databaseTables = [];
  }

  /**
   *
   */
  public function getInstance(string $identifier) {
    /* @var $resource \Dkan\Datastore\Resource */
    $resource = Resource::hydrate($identifier);
    $id = $resource->getId();

    if (!isset($this->databaseTables[$id])) {
      $this->databaseTables[$id] = $this->getDatabaseTable($resource);
    }

    return $this->databaseTables[$id];
  }

  /**
   * @codeCoverageIgnore
   */
  protected function getDatabaseTable($resource) {
    return new DatabaseTable($this->connection, $resource);
  }

}
