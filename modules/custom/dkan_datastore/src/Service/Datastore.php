<?php

namespace Drupal\dkan_datastore\Service;

use CsvParser\Parser\Csv;
use Dkan\Datastore\Importer;
use Dkan\Datastore\Resource;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Queue\QueueFactory;
use Drupal\dkan_datastore\Storage\DatabaseTable;
use Drupal\dkan_datastore\Storage\DatabaseTableFactory;
use Drupal\dkan_datastore\Storage\JobStore;
use Drupal\dkan_datastore\Service\ImporterList\ImporterList;
use Drupal\node\NodeInterface;
use FileFetcher\FileFetcher;
use Procrastinator\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Main services for the datastore.
 */
class Datastore implements ContainerInjectionInterface {

  const DATASTORE_DEFAULT_TIMELIMIT = 60;

  private $entityRepository;

  private $queue;

  private $databaseTableFactory;

  /**
   * File System.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  private $fileSystem;

  private $jobStore;

  /**
   * Inherited.
   *
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new Datastore(
      $container->get('entity.repository'),
      $container->get('queue'),
      $container->get('file_system'),
      $container->get('dkan_datastore.database_table_factory'),
      $container->get('dkan_datastore.job_store')
    );
  }

  /**
   * Constructor for datastore service.
   */
  public function __construct(
            EntityRepository $entityRepository,
            QueueFactory $queue,
            FileSystem $fileSystem,
            DatabaseTableFactory $databaseTableFactory,
            JobStore $jobStore
  ) {
    $this->entityRepository = $entityRepository;
    $this->queue = $queue->get('dkan_datastore_import');
    $this->fileSystem = $fileSystem;
    $this->jobStore = $jobStore;
    $this->databaseTableFactory = $databaseTableFactory;
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

    // If we passed $deferred, immediately add to the queue for later.
    if (!empty($deferred)) {
      $file_fetcher = $this->getFileFetcher($uuid);
      $this->jobStore->store($uuid, $file_fetcher);

      $queueId = $this->queueImport($uuid);
      return [
        'message' => "Resource {$uuid} has been queued to be imported.",
        'queue_id' => $queueId,
      ];
    }

    $file_fetcher = $this->getFileFetcher($uuid);
    $file_fetcher->run();

    // No matter what, create a record in the DB for this job.
    $this->jobStore->store($uuid, $file_fetcher);

    if ($file_fetcher->getResult()->getStatus() != Result::DONE) {
      return [get_class($file_fetcher) => $file_fetcher->getResult()];
    }

    $importer = $this->getImporter($uuid);

    // Otherwise, start the import immediately.
    $importer->runIt();

    // No matter what, create a record in the DB for this job.
    $this->jobStore->store($uuid, $importer);

    return ["FileFetcherResult" => $file_fetcher->getResult(), "ImporterResult" => $importer->getResult()];
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
    $this->jobStore->remove($uuid, Importer::class);
    $this->jobStore->remove($uuid, FileFetcher::class);
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
      $resource = $this->getResourceFromUuid($uuid, TRUE);
      $importer = new Importer($resource, $this->getStorage($uuid), Csv::getParser());
      $importer->setTimeLimit(self::DATASTORE_DEFAULT_TIMELIMIT);
      $this->jobStore->store($uuid, $importer);
    }
    if (!($importer instanceof Importer)) {
      throw new \Exception("Could not load importer for uuid $uuid");
    }
    return $importer;
  }

  /**
   * Get a list of all stored importers and filefetchers from database.
   *
   * @return \Drupal\dkan_datastore\Service\ImporterList\ImporterList
   *   The importer list object.
   */
  public function listStoredImporters() {
    return ImporterList::getList($this->jobStore);
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
    if ($importer = $this->jobStore->retrieve($uuid, Importer::class)) {
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
    return $this->databaseTableFactory->getInstance(json_encode($resource));
  }

  /**
   * Create a resource object from a node's UUID.
   *
   * @param string $uuid
   *   The UUID for a resource node.
   * @param bool $useFileFetcher
   *   If file fetcher was used, get path from the file fetcher.
   *
   * @return \Dkan\Datastore\Resource
   *   Datastore resource object.
   *
   * @throws \Exception
   *   If a file fetcher operation has not been completed.
   */
  public function getResourceFromUuid(string $uuid, $useFileFetcher = FALSE): Resource {
    $node = $this->entityRepository->loadEntityByUuid('node', $uuid);

    if ($useFileFetcher == TRUE) {
      $fileFetcher = $this->getFileFetcher($uuid);
      if ($fileFetcher->getResult()->getStatus() != Result::DONE) {
        throw new \Exception("The file fetcher has not finished.");
      }
      $fileData = json_decode($fileFetcher->getResult()->getData());
      return new Resource($node->id(), $fileData->destination);
    }
    else {
      return new Resource($node->id(), $this->getResourceFilePathFromNode($node));
    }
  }

  /**
   * Private.
   */
  private function getFileFetcher(string $uuid): FileFetcher {
    if (!$fileFetcher = $this->getStoredFileFetcher($uuid)) {
      $node = $this->entityRepository->loadEntityByUuid('node', $uuid);
      $file_path = $this->getResourceFilePathFromNode($node);

      $tmpDirectory = $this->fileSystem->realpath("public://") . "/dkan-tmp";
      $this->fileSystem->prepareDirectory($tmpDirectory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

      $fileFetcher = new FileFetcher($file_path, $tmpDirectory);

      $fileFetcher->setTimeLimit(self::DATASTORE_DEFAULT_TIMELIMIT);
      $this->jobStore->store($uuid, $fileFetcher);
    }
    if (!($fileFetcher instanceof FileFetcher)) {
      throw new \Exception("Could not load file-fetcher for uuid $uuid");
    }
    return $fileFetcher;
  }

  /**
   * Private.
   */
  private function getStoredFileFetcher(string $uuid) {
    if ($filefetcher = $this->jobStore->retrieve($uuid, FileFetcher::class)) {
      return $filefetcher;
    }
    return FALSE;
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
