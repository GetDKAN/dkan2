<?php

namespace Drupal\dkan_datastore\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Dkan\Datastore\Manager\IManager;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Queue\QueueInterface;

/**
 * Fetches the
 *
 * @QueueWorker(
 *   id = "dkan_datastore_file_fetcher_queue",
 *   title = @Translation("Fetches the file if necesary"),
 *   cron = {"time" = 1200}
 * )
 */
class FileFetcherQueue extends QueueWorkerBase {

  /**
   * {@inheritdocs}
   */
  public function processItem($data) {
    $uuid         = $data['uuid'];
    $resourceId   = $data['resource_id'];
    $filePath     = $data['file_path'];
    $importConfig = $data['import_config'];

    $actualFilePath = $this->fetchFile($uuid, $filePath);

    // there should only be one iteration of this queue
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->getImporterQueue();

    // queue is self calling and should keep going until complete.
    return $queue->createItem([
        'uuid'              => $uuid,
        // resource id is used to create table.
        // and has to be same as original
        'resource_id'       => $resourceId,
        'file_identifier'   => $this->sanitiseString($actualFilePath),
        'file_path'         => $actualFilePath,
        'import_config'     => $importConfig,
        'file_is_temporary' => $this->isFileTemporary($actualFilePath),
    ]);
  }

  /**
   * Tests if the file want to use is usable attempt to make it usable.
   *
   * @param string $uuid UUID
   * @param string $filePath file
   * @return string usable file path,
   * @throws \Exception If fails to get a usable file.
   */
  public function fetchFile(string $uuid, string $filePath): string {

    // is on local file system.
    if (is_readable($filePath)) {
      return $filePath;
    }

    // try to download the file some other way.
    // use fopen to allow for custom scheme handlers.
    $tmpFile = $this->getTemporaryFile($uuid);
    if (
      false !== ($source  = fopen($filePath, 'r')) && false !== ($dest    = fopen($tmpFile, 'w'))
    ) {

      // some streams can have weird pointer positions.
      @rewind($source);
      // this is more efficient way of copying files
      // and not subject to timeouts.
      $copyResult = stream_copy_to_stream($source, $dest);

      if (false === $copyResult) {
        throw new SuspendQueueException("Unable to fetch non local {$filePath} to {$tmpFile} for resource {$uuid}");
      }

      fclose($source);
      fclose($dest);

      return $tmpFile;
    }

    // failed to get the file
    throw new SuspendQueueException("Unable to fetch {$filePath} for resource {$uuid}");
  }

  /**
   * generate a tmp filepath for a given $uuid.
   *
   * @param string $uuid UUID
   * @return string
   */
  protected function getTemporaryFile(string $uuid): string {
    return file_directory_temp() . '/dkan-resource-' . $this->sanitiseString($uuid);
  }

  /**
   * Determine if the file is in the temporary fetched file.
   *
   * @param string $filePath
   * @return bool
   */
  protected function isFileTemporary(string $filePath): bool {
    return 0 === strpos($filePath, file_directory_temp() . '/dkan-resource-');
  }

  /**
   * Get the queue for the datastore_import.
   * @return \Drupal\Core\Queue\QueueInterface
   */
  public function getImporterQueue(): QueueInterface {
    return \Drupal::queue('dkan_datastore_import_queue');
  }

  protected function sanitiseString($string) {
    return preg_replace('~[^a-z0-9]+~', '_', strtolower($string));
  }

}
