<?php

namespace Drupal\dkan_datastore\Manager;

use Dkan\Datastore\Resource;
use Drupal\Core\Queue\QueueInterface;

/**
 * DeferredImport, uses resource information to add chunks to the import queue.
 *
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
class DeferredImportQueuer {

  /**
   * Split the resource to be processed by queue.
   * 
   * @param string $uuid
   * @param Resource $resource
   * @param int $chunkSize 
   * @return mixed ID of queue item created or false on failure.
   */
  public function createQueueItemsForResource(string $uuid, Resource $resource, array $importConfig = []) {

    $actualFile = $this->fetchFile($uuid, $resource->getFilePath());
    $queue      = $this->getQueue();

    // queue is self calling and should keep going until complete.
    return $queue->createItem([
        'uuid'              => $uuid,
        // resource id is used to create table.
        'resource_id'       => $resource->getId(),
        'file_identifier'   => $this->sanitiseString($actualFile),
        'file_path'         => $actualFile,
        'import_config'     => $importConfig,
        'file_is_temporary' => $this->isFileTemporary($actualFile),
        // some way of tracking stage
        'queue_iteration'   => 0,
        'rows_done'         => 0,
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

    // is an http request
    // @todo should try to fopen() the filepath and use stream_get_meta_data()
    //       to determine if it's seekable. datastore manager should be
    //       refactored to use fseek().
    if (true === in_array(
        parse_url($filePath, PHP_URL_SCHEME),
        ['http', 'https'],
        true
      )) {
      /** @var \GuzzleHttp\Client $httpClient */
      $httpClient = \Drupal::service('http_client');
      $tmpFile    = $this->getTemporaryFile($uuid);

      $httpClient->get($filePath, [
        'sink' => $tmpFile,
      ]);

      return $tmpFile;
    }

    // failed to get the file
    throw new \Exception("Unable to fetch {$filePath} for resource {$uuid}");
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
   * @return QueueInterface
   */
  public function getQueue(): QueueInterface {
    return \Drupal::queue('dkan_datastore_import_queue');
  }

  protected function sanitiseString($string) {
    return preg_replace('~[^a-z0-9]+~', '_', strtolower($string));
  }

}
