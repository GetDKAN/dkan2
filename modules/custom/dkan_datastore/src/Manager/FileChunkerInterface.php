<?php



namespace Drupal\dkan_datastore\Manager;

use Dkan\Datastore\Resource;

interface FileChunkerInterface {
  /**
   * This method splits a file resource into smaller chunks.
   *
   * @param Resource $resource source
   * @param int $chunkSize numerical value for size of chunk. meaning can vary.
   * @return array map of partId=>path to local chunked file.
   */
  public function chunkFile( Resource $resource, int $chunkSize):array;
}
