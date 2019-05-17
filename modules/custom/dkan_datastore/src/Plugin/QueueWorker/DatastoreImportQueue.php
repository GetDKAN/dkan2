<?php

namespace Drupal\dkan_datastore\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Dkan\Datastore\Manager\IManager;
use Drupal\Core\Queue\RequeueException;
/**
 * Processes resource import.
 *
 * @QueueWorker(
 *   id = "dkan_datastore_import_queue",
 *   title = @Translation("Queue to process datastore import"),
 *   cron = {"time" = 60}
 * )
 */
class DatastoreImportQueue extends QueueWorkerBase {

  public function processItem($data) {

    $uuid           = $data['uuid'];
    $fileIdentifier = $data['file_identifier'];
    $filePath       = $data['file_path'];

    /** @var \Drupal\dkan_datastore\Manager\DatastoreManagerBuilder $managerBuilder */
    $managerBuilder = \Drupal::service('dkan_datastore.manager.datastore_manager_builder');

    /** @var \Dkan\Datastore\Manager\IManager $manager */
    $manager = $managerBuilder->setResource($fileIdentifier, $filePath)
      ->build($uuid);

    $status = $manager->import();
    
    switch($status){
      case IManager::DATA_IMPORT_IN_PROGRESS:
        throw new RequeueException("{$uuid} {$fileIdentifier} requeued.");
        break;
      case IManager::DATA_IMPORT_DONE:
        // cleanup.
        \Drupal::service('file_system')
          ->unlink($filePath);
        break;
      // @todo add additional cases for error and paused.
    }
  }

}
