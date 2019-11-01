<?php

namespace Drupal\dkan_harvest\Storage;

use Contracts\FactoryInterface;
use Drupal\Core\Database\Connection;

class DatabaseTableFactory implements FactoryInterface
{
  private $connection;
  private $storage = [];

  public function __construct(Connection $connection)
  {
    $this->connection = $connection;
  }

  public function getInstance(string $identifier, array $config = [])
  {
    if (!isset($this->storage[$identifier])) {
      $this->storage[$identifier] = new DatabaseTable($this->connection, $identifier);
    }
    return $this->storage[$identifier];
  }


}
