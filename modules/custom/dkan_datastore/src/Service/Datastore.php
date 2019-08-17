<?php

namespace Drupal\dkan_datastore\Service;

use CsvParser\Parser\Csv;
use Dkan\Datastore\Importer;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\dkan_datastore\DeferredImportQueuer;
use Drupal\node\NodeInterface;
use Dkan\Datastore\Resource;
use Drupal\Core\Database\Connection;
use Drupal\dkan_datastore\Storage\DatabaseTable;

/**
 * Main services for the datastore.
 */
class Datastore {

  private $entityRepository;
  private $logger;
  private $connection;

  /**
   * Constructor for datastore service.
   */
  public function __construct(
            EntityRepository $entityRepository,
            LoggerChannelInterface $logger,
            Connection $connection
  ) {
    $this->entityRepository = $entityRepository;
    $this->logger = $logger;
    $this->connection = $connection;
  }

  /**
   * Start import process for a resource, provided by UUID.
   *
   * @param string $uuid
   *   UUID for resource node.
   * @param bool $deferred
   *   Send to the queue for later? Will import immediately if FALSE.
   */
  public function import($uuid, $deferred = FALSE) {
    if (!empty($deferred)) {
      $this->queueImport($uuid);
    }
    else {
      $this->processImport($uuid);
    }
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
  }

  /**
   * Queue a resource for import.
   *
   * @param string $uuid
   *   Resource node UUID.
   */
  private function queueImport($uuid) {
    $deferredImporter = new DeferredImportQueuer();
    $queueId = $deferredImporter->createDeferredResourceImport($uuid, $this->getResourceFromUuid($uuid));
    $this->logger->notice("New queue (ID:{$queueId}) was created for `{$uuid}`");
  }

  /**
   * Start a datastore import process for a distribution object.
   *
   * @param object $distribution
   *   Metadata distribution object decoded from JSON. Must have an $identifier.
   */
  private function processImport(string $uuid)
  {
    $importer = $this->getImporter($uuid);
    $importer->runIt();
  }

  /**
   * Build an Importer.
   *
   * @param \Dkan\Datastore\Resource $resource
   *   Resource.
   *
   * @return \Dkan\Datastore\Importer
   *   Importer.
   */
  private function getImporter(string $uuid): Importer
  {
    return new Importer($this->getResourceFromUuid($uuid), $this->getStorage($uuid), Csv::getParser());
  }

  public function getStorage(string $uuid): DatabaseTable {
    $resource = $this->getResourceFromUuid($uuid);
    return new DatabaseTable($this->connection, $resource);
  }

  /**
   * Given a Drupal node UUID, will create a resource object.
   *
   * @param string $uuid
   *   The UUID for a resource node.
   */
  public function getResourceFromUuid(string $uuid): Resource
  {
    $node = $this->entityRepository->loadEntityByUuid('node', $uuid);
    return new Resource($node->id(), $this->getResourceFilePathFromNode($node));
  }

  /**
   * Given a resource node object, return the path to the resource file.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A Drupal node entity (should be of resource type).
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
