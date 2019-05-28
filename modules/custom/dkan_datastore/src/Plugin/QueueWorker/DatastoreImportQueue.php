<?php

namespace Drupal\dkan_datastore\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Dkan\Datastore\Manager\IManager;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Logger\RfcLogLevel;

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

  use \Drupal\Core\Logger\LoggerChannelTrait;

  /**
   * Limit to how many stalled imports can occur before queue is stopped.
   */
  const STALL_LIMIT = 5;

  public function processItem($data) {

    $uuid            = $data['uuid'];
    $resourceId      = $data['resource_id'];
    $fileIdentifier  = $data['file_identifier'];
    $filePath        = $data['file_path'];
    $importConfig    = $data['import_config'];
    $fileIsTemporary = $data['file_is_temporary'] ?? false;

    // state of process
    $queueIteratation = $data['queue_iteration'] ?? 0;
    $rowsDone         = $data['rows_done'] ?? 0;
    $importFailCount  = $data['import_fail_count'] ?? 0;

    $manager = $this->getManager($uuid, $resourceId, $filePath, $importConfig);

    $status = $manager->import();

    // update the state as it were.
    $newRowsDone         = $manager->numberOfRecordsImported();
    $newQueueIteratation = $queueIteratation + 1;

    // try to detect if import is stalled.
    // it shouldn't go backwards but just in case..
    if ($newRowsDone - $rowsDone <= 0) {
      $importFailCount++;
      $this->log(RfcLogLevel::WARNING, "Import for {$uuid} seemd to be lagging behind {$importFailCount} times. Rows done:{$rowsDone} vs {$newRowsDone}");
    }

    switch ($status) {
      case IManager::DATA_IMPORT_IN_PROGRESS:
      case IManager::DATA_IMPORT_PAUSED:

        // suspend further processing.
        if ($importFailCount > static::STALL_LIMIT) {
          $this->log(RfcLogLevel::ERROR, "Import for {$uuid} lagged for {$importFailCount} times. Suspending.");
          throw new SuspendQueueException("Import for {$uuid}[{$filePath}] appears to have stalled past allowed limits.");
        }

        // requeue for next iteration.
        // queue is self calling and should keep going until complete.
        $newQueueItemId = $this->requeue($data, [
          'queue_iteration'   => $newQueueIteratation,
          'rows_done'         => $newRowsDone,
          'import_fail_count' => $importFailCount,
        ]);
        $this->log(RfcLogLevel::INFO, "Import for {$uuid} is requeueing {$newQueueIteratation} times. (ID:{$newQueueItemId}).");

        break;
      case IManager::DATA_IMPORT_ERROR:
        $this->log(RfcLogLevel::ERROR, "Import for {$uuid} returned an error.");

      case IManager::DATA_IMPORT_DONE:
        $this->log(RfcLogLevel::INFO, "Import for {$uuid} complete/stopped.");
        // cleanup.
        if ($fileIsTemporary) {
          \Drupal::service('file_system')
            ->unlink($filePath);
        }
        break;
    }
  }

  /**
   *
   * @param type $uuid
   * @param type $resourceId
   * @param string $filePath
   * @param array $importConfig
   * @return IManager
   */
  protected function getManager($uuid, $resourceId, string $filePath, array $importConfig) {
    /** @var \Drupal\dkan_datastore\Manager\DatastoreManagerBuilder $managerBuilder */
    $managerBuilder = \Drupal::service('dkan_datastore.manager.datastore_manager_builder');

    /** @var \Dkan\Datastore\Manager\IManager $manager */
    $manager = $managerBuilder->setResource($resourceId, $filePath)
      ->build($uuid);

    // forward config if applicable
    $manager->setConfigurableProperties($this->sanitiseImportConfig($importConfig));

    // set a slightly shorter time limit than cron run.
    $manager->setImportTimelimit(55);

    return $manager;
  }

  /**
   * Fixes some default import config.
   *
   * $importConfig has the following valid defaults:
   *
   * - 'delimiter' => ",",
   * - 'quote'     => '"',
   * - 'escape'    => "\\",
   *
   * @param array $importConfig
   * @return array Sanitised properties.
   * @todo this kind of validation should be moved to datastore manager.
   */
  public function sanitiseImportConfig(array $importConfig): array {
    $sanitised = array_merge([
      'delimiter' => ",",
      'quote'     => '"',
      'escape'    => "\\",
      ], $importConfig);

    return $sanitised;
  }

  protected function log($level, $message, array $context=[]) {
    $this->getLogger($this->getPluginId())
      ->log($level, $message, $context);
  }

  /**
   * Requeues the job with extra state information.
   *
   * @param array $data queue data
   * @param array $newState information on queue state.
   * @return mixed
   */
  protected function requeue(array $data, array $newState) {
    return \Drupal::queue($this->getPluginId())
        ->createItem(array_merge($data, $newState));
  }

}
