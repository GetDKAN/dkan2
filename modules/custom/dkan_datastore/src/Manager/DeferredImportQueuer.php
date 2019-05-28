<?php

namespace Drupal\dkan_datastore\Manager;

use Dkan\Datastore\Resource;
use Drupal\Core\Logger\RfcLogLevel;

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
   * @param array $importConfig
   * @return mixed ID of queue item created or false on failure.
   * @throws \RuntimeException
   */
  public function createDeferredResourceImport(string $uuid, Resource $resource, array $importConfig = []) {

    // attempt to fetch the file in a queue so as to not block user
    $queueId = \Drupal::queue('dkan_datastore_file_fetcher_queue')
      ->createItem([
        'uuid'          => $uuid,
        'resource_id'   => $resource->getId(),
        'file_path'     => $resource->getFilePath(),
        'import_config' => $importConfig,
    ]);

    if(false === $queueId)
    {
      throw new \RuntimeException("Failed to create file fetcher queue for {$uuid}");
    }

    return $queueId;

  }

}
