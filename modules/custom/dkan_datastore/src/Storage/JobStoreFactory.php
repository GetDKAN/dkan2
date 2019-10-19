<?php

namespace Drupal\dkan_datastore\Storage;

use Contracts\FactoryInterface;
use Drupal\Core\Database\Connection;

class JobStoreFactory implements FactoryInterface
{
  private $instances = [];
  private $connection;

  public function __construct(Connection $connection)
  {
    $this->connection = $connection;
  }

  public function getInstance(string $identifier)
  {
     if (!isset($this->instances[$identifier])) {
       $this->instances[$identifier] = new JobStore($identifier, $this->connection);
     }

     return $this->instances[$identifier];
  }

}
