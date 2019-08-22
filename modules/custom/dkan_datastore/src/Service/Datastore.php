<?php

namespace Drupal\dkan_datastore\Service;

use CsvParser\Parser\Csv;
use Dkan\Datastore\Importer;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\node\NodeInterface;
use Dkan\Datastore\Resource;
use Procrastinator\Result;
use Drupal\Core\Database\Connection;
use Drupal\dkan_datastore\Storage\DatabaseTable;
use Drupal\dkan_datastore\Storage\JobStore;

/**
 * Main services for the datastore.
 */
class Datastore {

  const DATASTORE_DEFAULT_TIMELIMIT = 60;

  private $entityRepository;
  private $logger;
  private $connection;
  private $queue;

  /**
   * Constructor for datastore service.
   */
  public function __construct(
            EntityRepository $entityRepository,
            LoggerChannelInterface $logger,
            Connection $connection,
            QueueFactory $queue
  ) {
    $this->entityRepository = $entityRepository;
    $this->logger = $logger;
    $this->connection = $connection;
    $this->queue = $queue->get('dkan_datastore_import');
  }

  /**
   * Start import process for a resource, provided by UUID.
   *
   * @param string $uuid
   *   UUID for resource node.
   * @param bool $deferred
   *   Send to the queue for later? Will import immediately if FALSE.
   */
  public function import(string $uuid, bool $deferred = FALSE): Result {
    $importer = $this->getImporter($uuid);

    // If we passed $deferred, immediately add to the queue for later.
    if (!empty($deferred)) {
      $this->queueImport($uuid);
    }
    // Otherwise, start the import immidiately.
    else {
      $importer->runIt();
    }

    // No matter what, create a record in the DB for this job.
    $jobStore = new JobStore($this->connection);
    $jobStore->store($uuid, $importer);

    return $importer->getResult();
  }

  /**
   * Drop all datastores for a given node.
   *
   * @param string $uuid
   *   UUID for resource or dataset node. If dataset, will drop datastore for
   *   all connected resources.
   */
  public function drop($uuid) {
    $this->getStorage($uuid)->destroy();
    $jobStore = new JobStore($this->connection);
    $jobStore->remove($uuid, Importer::class);
  }

  /**
   * Queue a resource for import.
   *
   * @param string $uuid
   *   Resource node UUID.
   */
  private function queueImport($uuid) {
    // Attempt to fetch the file in a queue so as to not block user.
    $queueId = $this->queue->createItem(['uuid' => $uuid]);

    if ($queueId === FALSE) {
      throw new \RuntimeException("Failed to create file fetcher queue for {$uuid}");
    }

    $this->logger->notice("New queue (ID:{$queueId}) was created for `{$uuid}`");
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
  private function getImporter(string $uuid): Importer {
    if (!$importer = $this->getStoredImporter($uuid)) {
      $importer = new Importer($this->getResourceFromUuid($uuid), $this->getStorage($uuid), Csv::getParser());
      $importer->setTimeLimit(self::DATASTORE_DEFAULT_TIMELIMIT);
    }
    if (!($importer instanceof Importer)) {
      throw new \Exception("Could not load importer for uuid $uuid");
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
  private function getStoredImporter(string $uuid) {
    $jobStore = new JobStore($this->connection);
    if ($importer = $jobStore->get($uuid, Importer::class)) {
      return $importer;
    }
    return FALSE;
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
  public function getStorage(string $uuid): DatabaseTable {
    $resource = $this->getResourceFromUuid($uuid);
    return new DatabaseTable($this->connection, $resource);
  }

  /**
   * Given a Drupal node UUID, will create a resource object.
   *
   * @param string $uuid
   *   The UUID for a resource node.
   *
   * @return Dkan\Datastore\Resource
   *   Datastore resource object.
   */
  public function getResourceFromUuid(string $uuid): Resource {
    $node = $this->entityRepository->loadEntityByUuid('node', $uuid);
    return new Resource($node->id(), $this->getResourceFilePathFromNode($node));
  }

  /**
   * Given a resource node object, return the path to the resource file.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A Drupal node.
   *
   * @return string
   *   File path.
   *
   * @throws \Exception
   *   Throws exception if validation of entity or data fails.
   */
  private function getResourceFilePathFromNode(NodeInterface $node): string {

    $meta = $node->get('field_json_metadata')->get(0)->getValue();

    if (!isset($meta['value'])) {
      throw new \Exception("Entity for {$node->uuid()} does not have required field `field_json_metadata`.");
    }

    $metadata = json_decode($meta['value']);

    if (!($metadata instanceof \stdClass)) {
      throw new \Exception("Invalid metadata information or missing file information.");
    }

    if (isset($metadata->data->downloadURL)) {
      return $metadata->data->downloadURL;
    }

    if (isset($metadata->distribution[0]->downloadURL)) {
      return $metadata->distribution[0]->downloadURL;
    }

    throw new \Exception("Invalid metadata information or missing file information.");
  }

}
