<?php

namespace Drupal\dkan_datastore\Service;

use CsvParser\Parser\Csv;
use Dkan\Datastore\Importer;
use Drupal\dkan_datastore\Storage\DatabaseTable;
use Drupal\dkan_datastore\Storage\JobStore;
use Procrastinator\Result;
use Dkan\Datastore\Resource;
use Drupal\dkan_datastore\Storage\DatabaseTableFactory;

/**
 *
 */
class Import {
  const DEFAULT_TIMELIMIT = 50;

  private $resource;
  private $jobStore;
  private $databaseTableFactory;

  /**
   *
   */
  public function __construct(Resource $resource, JobStore $jobStore, DatabaseTableFactory $databaseTableFactory) {
    $this->resource = $resource;
    $this->jobStore = $jobStore;
    $this->databaseTableFactory = $databaseTableFactory;
  }

  /**
   *
   */
  public function import() {
    $importer = $this->getImporter();
    $importer->run();
    $this->jobStore->store($this->resource->getId(), $importer);
  }

  /**
   *
   */
  public function getResult(): Result {
    $importer = $this->getImporter();
    return $importer->getResult();
  }

  /**
   * Build an Importer.
   *
   * @param string $uuid
   *   UUID for resrouce node.
   *
   * @return \Dkan\Datastore\Importer
   *   Importer.
   *
   * @throws \Exception
   *   Throws exception if cannot create valid importer object.
   */
  private function getImporter(): Importer {
    if (!$importer = $this->getStoredImporter()) {
      $importer = new Importer($this->resource, $this->getStorage(), Csv::getParser());
      $importer->setTimeLimit(self::DEFAULT_TIMELIMIT);
      $this->jobStore->store($this->resource->getId(), $importer);
    }
    if (!($importer instanceof Importer)) {
      throw new \Exception("Could not load importer for resource {$this->resource->getId()}");
    }
    return $importer;
  }

  /**
   * Get a stored importer.
   *
   * @param string $uuid
   *   Resource node UUID.
   *
   * @return Dkan\Datastore\Importer|bool
   *   Importer object or FALSE if none found.
   */
  private function getStoredImporter(): ?Importer {
    if ($importer = $this->jobStore->retrieve($this->resource->getId(), Importer::class)) {
      return $importer;
    }
    return NULL;
  }

  /**
   * Build a database table storage object.
   *
   * @param string $uuid
   *   Resource node UUID.
   *
   * @return \Drupal\dkan_datastore\Storage\DatabaseTable
   *   DatabaseTable storage object.
   */
  public function getStorage(): DatabaseTable {
    return $this->databaseTableFactory->getInstance(json_encode($this->resource));
  }

}
