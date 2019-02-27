<?php

namespace Drupal\dkan_harvest;

use Drupal\dkan_api\Storage\DrupalNodeDataset;
use Drupal\dkan_harvest\Log\MakeItLog;
use Drupal\dkan_harvest\Storage\Hash;

class Reverter {
  use MakeItLog;

  public $sourceId;

  function __construct($sourceId) {
    $this->sourceId = $sourceId;
  }

  function run() {
    $this->log('DEBUG', 'revert', 'Reverting harvest ' . $this->sourceId);

    $hash_storage = new Hash();

    $uuids = $hash_storage->readIdsBySource($this->sourceId);

    $datastore_storage = new DrupalNodeDataset();

    $counter = 0;
    foreach ($uuids as $uuid) {
      $datastore_storage->remove($uuid);
      $counter++;
    }
    return $counter;
  }

}
