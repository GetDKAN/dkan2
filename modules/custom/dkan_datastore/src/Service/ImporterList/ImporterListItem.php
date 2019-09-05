<?php

namespace Drupal\dkan_datastore\Service\ImporterList;

use FileFetcher\FileFetcher;

/**
 * Defines and provide a single item for an ImporterList.
 */
class ImporterListItem {

  /**
   * FileFetcher object.
   *
   * @var \FileFetcher\FileFetcher
   */
  private $fileFetcher;

  /**
   * Datastore importer object.
   *
   * @var \Dkan\Datastore\Importer
   */
  private $importer;

  /**
   * File fetcher job result object.
   *
   * @var \Procrastinator\Result
   */
  public $fileFetcherResult;

  /**
   * File fetcher job result status code. See result class for code definitions.
   *
   * @var string
   * @see \Procrastinator\Result::STOPPED
   * @see \Procrastinator\Result::IN_PROGRESS
   * @see \Procrastinator\Result::ERROR
   * @see \Procrastinator\Result::DONE
   */
  public $fileFetcherStatus;

  /**
   * File name (without path) for the resource.
   *
   * @var string
   */
  public $fileName;

  /**
   * Importer job result status code. See result class for code definitions.
   *
   * @var string
   * @see \Procrastinator\Result::STOPPED
   * @see \Procrastinator\Result::IN_PROGRESS
   * @see \Procrastinator\Result::ERROR
   * @see \Procrastinator\Result::DONE
   */
  public $importerStatus;

  /**
   * Number of bytes processed in file if importer has run.
   *
   * Note that this is calculated by multiplying the chunks by 32, which may
   * result in the total being slightly off.
   *
   * @var int
   */
  public $bytesProcessed;

  /**
   * The percentage of the file that has been imported into the datastore.
   *
   * @var float
   */
  public $percentDone;

  /**
   * Constructor method.
   *
   * @param \FileFetcher\FileFetcher $fileFetcher
   *   FileFetcher job object.
   * @param \Dkan\Datastore\Importer|null $importer
   *   Datastore importer job object, or NULL if one does not exist.
   */
  public function __construct(FileFetcher $fileFetcher, $importer = NULL) {
    $this->fileFetcher = $fileFetcher;
    $this->importer = $importer;
  }

  /**
   * Static function to build a full object with a single call.
   *
   * @param \FileFetcher\FileFetcher $fileFetcher
   *   The FileFetcher object.
   * @param \Dkan\Datastore\Importer|null $importer
   *   Importer object.
   */
  public static function getItem(FileFetcher $fileFetcher, $importer = NULL) {
    $item = new ImporterListItem($fileFetcher, $importer);
    $item->buildItem();
    return $item;
  }

  /**
   * Build out the full "item" object and set public propertries.
   */
  private function buildItem() {
    $this->fileFetcherStatus = $this->fileFetcher->getResult()->getStatus();
    $this->importerStatus = 'waiting';
    $this->bytesProcessed = 0;
    $this->percentDone = 0;
    $this->fileName = $this->getFileName();

    if (isset($importer)) {
      $this->importerStatus = $this->importer->getResult()->getStatus();
      $this->bytesProcessed = $this->getBytesProcessed();
      $this->percentDone = $this->getPercentDone();
    }
  }

  /**
   * Using the fileFetcher object, find the file path and extract the name.
   */
  private function getFileName(): string {
    $data = json_decode($this->fileFetcher->getResult()->getData());
    $fileLocation = $data->source;
    $locationParts = explode('/', $fileLocation);
    return end($locationParts);
  }

  /**
   * Using the importer job object, get a percentage of the total file imported.
   *
   * @return float
   *   Percentage.
   */
  private function getPercentDone(): float {
    $bytes = $this->getBytesProcessed($this->importer);
    return round($bytes / filesize($this->importer->getResource()->getFilePath()) * 100);
  }

  /**
   * Calculate bytes processed based on chunks processed in the importer data.
   *
   * @return int
   *   Total bytes processed.
   *
   * @todo Prevent this from going above the total number of bytes in file.
   */
  private function getBytesProcessed(): int {
    $data = json_decode($this->importer->getResult()->getData());
    return $data->chunksProcessed * 32;
  }

}
