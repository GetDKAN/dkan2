<?php

namespace Drupal\dkan_api\Storage;

use Drupal\dkan_common\Storage\StorageInterface;

/**
 *
 */
class Organization implements StorageInterface {
  private $datasetStorage;

  /**
   *
   */
  public function __construct(DrupalNodeDataset $datasetStorage) {
    $this->datasetStorage = $datasetStorage;
  }

  /**
   *
   */
  public function retrieveAll():array {
    $organizations = [];
    $datasets = json_decode($this->datasetStorage->retrieveAll());
    foreach ($datasets as $dataset) {
      if ($organization = $dataset->organization) {
        $organizations[$organization] = $organization;
      }
    }
    $values = array_values($organizations);
    return json_encode($values);
  }

  /**
   *
   */
  public function retrieve(string $id): string {
    // TODO: Implement retrieve() method.
  }

  /**
   *
   */
  public function store(string $data, string $id = NULL): string {
    // TODO: Implement store() method.
  }

  /**
   *
   */
  public function remove(string $id) {
    // TODO: Implement remove() method.
  }

}
