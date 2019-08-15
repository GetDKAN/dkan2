<?php

namespace Drupal\dkan_datastore\Service;

use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\node\NodeInterface;
use Dkan\Datastore\Resource;
use Drupal\Core\Database\Connection;
use Drupal\dkan_datastore\Storage\DatabaseTable;

/**
 * Main services for the datastore.
 */
class Datastore {

  protected $entityRepository;
  protected $logger;
  private $storage;
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
      $this->queueImport($uuid, $this->getResource($uuid));
    }
    else {
      $this->processImport($distribution);
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

    foreach ($this->getDistributionsFromUuid($uuid) as $distribution) {
      $this->processDrop($distribution);
    }
  }

  /**
   * Queue a resource for import.
   *
   * @param string $uuid
   *   Resource node UUID.
   * @param Dkan\Datastore\Resource $resource
   *   Datastore resource object.
   */
  protected function queueImport($uuid, Resource $resource) {
    /** @var \Drupal\dkan_datastore\Importer\DeferredImportQueuer $deferredImporter */
    $deferredImporter = \Drupal::service('dkan_datastore.deferred_import_queuer');
    $queueId          = $deferredImporter->createDeferredResourceImport($uuid, $resource);
    $this->logger->notice("New queue (ID:{$queueId}) was created for `{$uuid}`");
  }

  /**
   * Start a datastore import process for a distribution object.
   *
   * @param object $distribution
   *   Metadata distribution object decoded from JSON. Must have an $identifier.
   */
  protected function processImport($distribution) {
    $datastore = $this->getDatastore($this->getResource($distribution));
    $datastore->runIt();
  }

  /**
   * Drop a datastore for a given distribution object.
   *
   * @param object $distribution
   *   Metadata distribution object decoded from JSON. Must have an $identifier.
   */
  protected function processDrop($distribution) {
    $datastore = $this->getDatastore($this->getResource($distribution));
    $datastore->drop();
  }

  /**
   * Create a datastore Resource object from distribution metadata.
   *
   * @param object $distribution
   *   Metadata distribution object decoded from JSON. Must have an $identifier.
   *
   * @return Dkan\Datastore\Resource
   *   Resource object.
   */
  protected function getResource($distribution) {
    $distribution_node = $this->helper
      ->loadNodeByUuid($distribution->identifier);

    return $this->helper
      ->newResource($distribution_node->id(), $distribution->data->downloadURL);
  }

  /**
   * Build a datastore Manager from a resource object.
   *
   * @param Dkan\Datastore\Resource $resource
   *   Datastore resource object.
   *
   * @return Dkan\Datastore\Manager
   *   Datastore manager object.
   */
  protected function getDatastore(Resource $resource) {
    /* @var  $builder  Builder */
    $builder = \Drupal::service('dkan_datastore.importer.builder');
    $builder->setResource($resource);
    return $builder->build();
  }

  public function getStorage(string $uuid): DatabaseTable {
    $resource = $this->getResourceFromUuid($uuid);
    return new DatabaseTable($this->connection, $resource);
  }

  /**
   * Get one or more distributions (aka resources) from a uuid.
   *
   * @param string $uuid
   *   Dataset node UUID.
   *
   * @return object
   *   Distribution metadata object decoded from JSON.
   */
  protected function getDistributionsFromUuid($uuid) {

    $node = $this->helper
      ->loadNodeByUuid($uuid);

    if (!($node instanceof NodeInterface) || 'data' !== $node->getType()) {
      $this->logger->error("We were not able to load a data node with uuid {$uuid}.");
      return [];
    }
    // Verify data is of expected type.
    $expectedTypes = [
      'dataset',
      'distribution',
    ];
    if (!isset($node->field_data_type->value) || !in_array($node->field_data_type->value, $expectedTypes)) {
      $this->logger->error("Data not among expected types: " . implode(" ", $expectedTypes));
      return [];
    }
    // Standardize whether single resource object or several in a dataset.
    $metadata      = json_decode($node->field_json_metadata->value);
    $distributions = [];
    if ($node->field_data_type->value == 'dataset') {
      $distributions = $metadata->distribution;
    }
    if ($node->field_data_type->value == 'distribution') {
      $distributions[] = $metadata;
    }

    return $distributions;
  }

  /**
   * Given a Drupal node UUID, will create a resource object.
   *
   * @param string $uuid
   *   The UUID for a resource node.
   */
  public function getResourceFromUuid($uuid): Resource {
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

  /**
   * Load a node object by its UUID.
   *
   * @param string $uuid
   *   The UUID of the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A node object.
   *
   * @throws \Exception
   *
   * @todo probably remove or at least make private
   */
  public function loadNodeByUuid(string $uuid): EntityInterface {
    $node = $this->entityRepository->loadEntityByUuid('node', $uuid);

    if (!($node instanceof Node)) {
      throw new \Exception("Node {$uuid} could not be loaded.");
    }

    return $node;
  }

  /**
   * Build datastore importer with set params, otherwise defaults.
   *
   * @return \Dkan\Datastore\Importer
   *   A built Importer object for the datastore.
   */
  public function getImporter(string $uuid): Importer {

    $resource = $this->getResourceFromUuid;

    return new Importer(
      $resource,
      $this->getDatabaseForResource($resource),
      Csv::getParser()
    );
  }
}
