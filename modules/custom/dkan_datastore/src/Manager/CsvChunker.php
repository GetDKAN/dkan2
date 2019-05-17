<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\dkan_datastore\Manager;

use Dkan\Datastore\Resource;

/**
 * Splits a csv resource to smaller chunks.
 *
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
class CsvChunker implements FileChunkerInterface {

  /**
   *
   * @param Resource $resource
   * @param int $chunkSize number of csv lines per chunk.
   * @return array map of partId=>path to local chunked file.
   */
  public function chunkFile(Resource $resource, int $chunkSize): array {

    $resourceId   = $resource->getId();
    $resourceFile = $resource->getFilePath();
    $tmpDir       = $this->getTemporaryDirectory();

    $csvResource = fopen($resourceFile, 'r');

    if (false === $csvResource) {
      throw new \Exception("Failed to open csv file for reading: {$resourceFile}");
    }

    if ($this->hasHeaderLine()) {
      $header            = fgetcsv($csvResource);
      $currentChunkLines = [$header];
    }

    $lineCount = 0;
    $partCount = 1;
    $chunks    = [];

    do {

      $line = fgetcsv($csvResource);

      if (
      // have a whole chunk
        $lineCount > $chunkSize
        // or end of file.
        || (false === $line)
      ) {
        // write current chunk and move on to next
        $partIdentifier = $resourceId . '.part-' . str_pad("$partCount", 6, '0', STR_PAD_LEFT);
        $partFilename = $tmpDir . '/' . $partIdentifier . '.csv';
        
        $this->writeCsvChunk($partFilename, $currentChunkLines);

        $chunks[$partIdentifier] = $partFilename;
        $partCount++;

        // prep the next chunk
        $currentChunkLines = [$header];
        $lineCount         = 0;
      }

      $currentChunkLines[] = $line;
      $lineCount++;
    } while (false !== $line);

    return $chunks;
  }

  /**
   * 
   * @param string $filename
   * @param array $lines
   * @throws \Exception if the file can't
   */
  protected function writeCsvChunk(string $filename, array $lines) {
    $handle = fopen($filename, 'w');

    if (false === $handle) {
      throw new \Exception("Failed to open chunk file for writing. {$filename}");
    }

    foreach ($lines as $line) {
      fputcsv($handle, $line);
    }
    fclose($handle);
  }

  /**
   * 
   * @todo Currently assumes there's always a header. make configurable?
   * @return boolean
   */
  protected function hasHeaderLine() {
    return true;
  }

  /**
   *
   * @return string
   */
  protected function getTemporaryDirectory() {
    return file_directory_temp();
  }

}
