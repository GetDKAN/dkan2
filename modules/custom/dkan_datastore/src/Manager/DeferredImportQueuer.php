<?php


namespace Drupal\dkan_datastore\Manager;

use Dkan\Datastore\Resource;
use Drupal\Core\Queue\QueueInterface;

/**
 * DeferredImport, uses resource information to add chunks to the import queue.
 *
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
class DeferredImportQueuer{
  

  /**
   * Split the resource to be processed by queue.
   * 
   * @param string $uuid
   * @param Resource $resource
   * @param int $chunkSize 
   * @return int Number of items queued.
   */
  public function createQueueItemsForResource(string$uuid, Resource $resource, int $chunkSize=500) {

    /** @var FileChunkerInterface $fileChunker */
    $fileChunker = \Drupal::service('dkan_datastore.manager.csv_chunker');
    
    $fileChunks = $fileChunker->chunkFile($resource, $chunkSize);
    
    $queue = $this->getQueueForDatastore($uuid);
    
    foreach ($fileChunks as $fileIdentifier => $filePath) {
      $queue->createItem([
        'uuid'            => $uuid,
        'file_identifier' => $fileIdentifier,
        'file_path'       => $filePath,
      ]);
    }

    return $queue->numberOfItems();
  }
  
  /**
   * Get the queue for the datastore_import.
   * @return QueueInterface
   */
  public function getQueueForDatastore(): QueueInterface {
    return \Drupal::queue('dkan_datastore_import_queue');
  }

}
