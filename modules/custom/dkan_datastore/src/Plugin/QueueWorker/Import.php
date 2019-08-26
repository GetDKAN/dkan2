<?php

namespace Drupal\dkan_datastore\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Dkan\Datastore\Importer;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Logger\RfcLogLevel;
use Procrastinator\Result;

/**
 * Processes resource import.
 *
 * @QueueWorker(
 *   id = "dkan_datastore_import",
 *   title = @Translation("Queue to process datastore import"),
 *   cron = {"time" = 60}
 * )
 */
class Import extends QueueWorkerBase {

  use \Drupal\Core\Logger\LoggerChannelTrait;

  /**
   * Limit to how many stalled imports can occur before queue is stopped.
   */
  const STALL_LIMIT = 5;

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    $datastore = \Drupal::service('dkan_datastore.service');

    $results = $datastore->import($data['uuid']);

    foreach ($results as $result) {
      switch ($result->getStatus()) {
        case Result::STOPPED:

          // Requeue for next iteration.
          // queue is self calling and should keep going until complete.
          $newQueueItemId = $this->requeue($data);

          $this->log(RfcLogLevel::INFO, "Import for {$data['uuid']} is requeueing for iteration No. {$data['queue_iteration']}. (ID:{$newQueueItemId}).");

          break;

        case Result::IN_PROGRESS:
        case Result::ERROR:

          // @todo fall through to cleanup on error. maybe should not so we can inspect issues further?
          $this->log(RfcLogLevel::ERROR, "Import for {$data['uuid']} returned an error: {$result->getError()}");
          break;

        case Result::DONE:
          $this->log(RfcLogLevel::INFO, "Import for {$data['uuid']} complete/stopped.");
          break;
      }
    }
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
