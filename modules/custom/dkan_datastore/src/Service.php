<?php

namespace Drupal\dkan_datastore;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\dkan_datastore\Service\Factory\Resource;
use Drupal\dkan_datastore\Service\Factory\Import;
use Drupal\dkan_datastore\Service\ImporterList\ImporterList;

/**
 * Main services for the datastore.
 */
class Service implements ContainerInjectionInterface {

  private $resourceServiceFactory;
  private $importServiceFactory;
  private $queue;

  /**
   * Inherited.
   *
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new Service(
      $container->get('dkan_datastore.service.factory.resource'),
      $container->get('dkan_datastore.service.factory.import'),
      $container->get('queue')
    );
  }

  /**
   * Constructor for datastore service.
   */
  public function __construct(Resource $resourceServiceFactory, Import $importServiceFactory, QueueFactory $queue) {
    $this->queue = $queue->get('dkan_datastore_import');
    $this->resourceServiceFactory = $resourceServiceFactory;
    $this->importServiceFactory = $importServiceFactory;
  }

  /**
   * Start import process for a resource, provided by UUID.
   *
   * @param string $uuid
   *   UUID for resource node.
   * @param bool $deferred
   *   Send to the queue for later? Will import immediately if FALSE.
   */
  public function import(string $uuid, bool $deferred = FALSE): array {

    $resourceService = $this->resourceServiceFactory->getInstance($uuid);
    $resource = $resourceService->get();

    // If we passed $deferred, immediately add to the queue for later.
    if (!empty($deferred)) {
      $queueId = $this->queueImport($uuid);
      return [
        'message' => "Resource {$uuid} has been queued to be imported.",
        'queue_id' => $queueId,
      ];
    }

    if (!$resource) {
      return [get_class($resourceService) => $resourceService->getResult()];
    }

    $importService = $this->importServiceFactory->getInstance(json_encode($resource));
    $importService->import();

    return [
      get_class($resourceService) => $resourceService->getResult(),
      get_class($importService) => $importService->getResult()
    ];
  }

  /**
   * Drop all datastores for a given node.
   *
   * @param string $uuid
   *   UUID for resource or dataset node. If dataset, will drop datastore for
   *   all connected resources.
   */
  public function drop($uuid) {
    /*$this->getStorage($uuid)->destroy();
    $this->jobStore->remove($uuid, Importer::class);
    $this->jobStore->remove($uuid, FileFetcher::class);*/
  }

  /**
   * Queue a resource for import.
   *
   * @param string $uuid
   *   Resource node UUID.
   *
   * @return int
   *   Queue ID for new queued item.
   */
  private function queueImport($uuid) {
    // Attempt to fetch the file in a queue so as to not block user.
    $queueId = $this->queue->createItem(['uuid' => $uuid]);

    if ($queueId === FALSE) {
      throw new \RuntimeException("Failed to create file fetcher queue for {$uuid}");
    }

    return $queueId;
  }

  /**
   * Get a list of all stored importers and filefetchers, and their status.
   *
   * @return \Drupal\dkan_datastore\Service\ImporterList\ImporterList
   *   The importer list object.
   */
  public function list() {
    return ImporterList::getList($this->jobStore);
  }
}
