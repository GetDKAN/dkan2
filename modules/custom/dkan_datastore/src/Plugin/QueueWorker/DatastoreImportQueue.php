<?php

namespace Drupal\dkan_datastore\Plugin\QueueWorker;

use Dkan\Datastore\Resource;
use Drupal\Core\Queue\QueueWorkerBase;
use Dkan\Datastore\Importer;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Logger\RfcLogLevel;
use Procrastinator\Result;

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

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    $data = $this->sanitizeData($data);

    $importer = $this->getImporter($data['resource_id'], $data['file_path'], $data['import_config']);

    $status = $importer->runIt();

    switch ($importer->getResult()->getStatus()) {
      case Result::IN_PROGRESS:
      case Result::STOPPED:

        $data = $this->refreshQueueState($data, $importer);

        // Requeue for next iteration.
        // queue is self calling and should keep going until complete.
        $newQueueItemId = $this->requeue($data);

        $this->log(RfcLogLevel::INFO, "Import for {$data['uuid']} is requeueing for iteration No. {$data['queue_iteration']}. (ID:{$newQueueItemId}).");

        break;

      case Result::ERROR:

        // @todo fall through to cleanup on error. maybe should not so we can inspect issues further?
        $this->log(RfcLogLevel::ERROR, "Import for {$data['uuid']} returned an error.");

      case Result::DONE:

        $this->log(RfcLogLevel::INFO, "Import for {$data['uuid']} complete/stopped.");

        // cleanup.
        $this->cleanup($data);

        break;
    }
  }

  /**
   * Sanitise input data for item processing.
   *
   * @param array $data
   *   Incoming data array.
   *
   * @return array
   *   Sanatized version of data array.
   */
  protected function sanitizeData(array $data): array {

    if (!isset($data['uuid'], $data['resource_id'], $data['file_path'])) {
      throw new SuspendQueueException('Queue input data is invalid. Missing required `uuid` or `resource_id`, `file_path`');
    }

    $data['import_config']     = $data['import_config'] ?? [];
    $data['file_is_temporary'] = $data['file_is_temporary'] ?? FALSE;

    // State of process.
    $data['queue_iteration']   = $data['queue_iteration'] ?? 0;
    $data['rows_done']         = $data['rows_done'] ?? 0;
    $data['import_fail_count'] = $data['import_fail_count'] ?? 0;

    return $data;
  }

  /**
   * Update and validate the state of the queue on success/pause.
   *
   * @param array $data
   *   The state of the queue.
   * @param \Dkan\Datastore\Importer $importer
   *   Datastore importer.
   *
   * @return array
   *   Data with updated state info.
   *
   * @throws \SuspendQueueException
   *   If the state is invalid.
   */
  protected function refreshQueueState(array $data, Importer $importer): array {
    // Update the state as it were.
    $newRowsDone = $importer->numberOfRecordsImported();

    // Try to detect if import is stalled.
    // it shouldn't go backwards but just in case..
    if ($newRowsDone - $data['rows_done'] <= 0) {
      $data['import_fail_count']++;
      $this->log(RfcLogLevel::WARNING, "Import for {$data['uuid']} seemd to be lagging behind {$data['import_fail_count']} times. Rows done:{$data['rows_done']} vs {$newRowsDone}");
    }

    // Suspend further processing.
    if ($data['import_fail_count'] > static::STALL_LIMIT) {
      $this->log(RfcLogLevel::ERROR, "Import for {$data['uuid']} lagged for {$data['import_fail_count']} times. Suspending.");
      throw new SuspendQueueException("Import for {$data['uuid']}[{$data['file_path']}] appears to have stalled past allowed limits.");
    }

    // Otherwise we can keep going.
    $data['queue_iteration']++;
    $data['rows_done'] = $newRowsDone;

    return $data;
  }

  /**
   * Perfoms cleanup based on input data.
   *
   * @todo Document more clearly cleanup/sanitize.
   *
   * @param array $data
   *   Data array to clean up.
   */
  protected function cleanup(array $data) {
    if ($data['file_is_temporary']) {
      \Drupal::service('file_system')->unlink($data['file_path']);
    }
  }

  /**
   * Create a datastore importer object.
   *
   * @param mixed $resourceId
   *   Node ID for resource node.
   * @param string $filePath
   *   File path for resource.
   * @param array $importConfig
   *   Import configuration. @todo Document better.
   *
   * @return \Dkan\Datastore\Importer
   *   Datastore importer object.
   */
  protected function getImporter($resourceId, string $filePath, array $importConfig) {
    /** @var \Drupal\dkan_datastore\Importer\Builder $importerBuilder */
    $importerBuilder = \Drupal::service('dkan_datastore.importer.builder');

    /** @var \Dkan\Datastore\Importer $importer */
    $importer = $importerBuilder->setResource(new Resource($resourceId, $filePath))
      ->build();

    // Forward config if applicable.
    $importer->setConfigurableProperties($this->sanitizeImportConfig($importConfig));

    // Set a slightly shorter time limit than cron run.
    $importer->setImportTimelimit(55);

    return $importer;
  }

  /**
   * Fixes some default import config.
   *
   * @param array $importConfig
   *   Import configuration array containging:
   *   - delimiter:  CSV delimiter (",")
   *   - quote:  Field wrapper ('"')
   *   - escape  Escape character ("\\")
   *
   * @return array
   *   Sanitised properties.
   *
   * @todo this kind of validation should be moved to datastore importer.
   */
  public function sanitizeImportConfig(array $importConfig): array {
    $sanitized = array_merge([
      'delimiter' => ",",
      'quote'     => '"',
      'escape'    => "\\",
    ], $importConfig);

    return $sanitized;
  }

  /**
   * Log a datastore event.
   */
  protected function log($level, $message, array $context = []) {
    $this->getLogger($this->getPluginId())
      ->log($level, $message, $context);
  }

  /**
   * Requeues the job with extra state information.
   *
   * @param array $data
   *   Queue data.
   *
   * @return mixed
   *   Queue ID or false if unsuccessfull.
   *
   * @todo: Clarify return value. Documentation suggests it should return ID.
   */
  protected function requeue(array $data) {
    return \Drupal::service('queue')
      ->get($this->getPluginId())
      ->createItem($data);
  }

}
