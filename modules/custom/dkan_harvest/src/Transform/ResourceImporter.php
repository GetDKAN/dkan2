<?php

namespace Drupal\dkan_harvest\Transform;

use Harvest\Transform\Transform;
use Drupal\dkan_harvest\Load\FileHelper;

/**
 * Defines a transform that saves the resources from a dataset.
 */
class ResourceImporter extends Transform {

  /**
   * The file helper object.
   *
   * @var Drupal\dkan_harvest\Load\FileHelper
   */
  protected $fileHelper;

  /**
   * ResourceImporter constructor.
   *
   * @param object $harvest_plan
   *   JSON decoded harvest plan.
   */
  public function __construct($harvest_plan) {
    parent::__construct($harvest_plan);
    $this->fileHelper = new FileHelper();
  }

  /**
   * {@inheritdoc}
   */
  public function run(&$items) {
    foreach ($items as $key => $item) {
      foreach ($item->distribution as $index => $dist) {
        if (isset($dist->downloadURL)) {
          // Attempt to save a resource file locally.
          $file_path = $this->saveFile($dist->downloadURL, $item->identifier);
          if ($file_path) {
            // Update resource URL.
            $items[$key]->distribution[$index]->downloadURL = $file_path;
          }
        }
      }
    }
  }

  /**
   * Pulls down external file and saves it locally.
   *
   * If this method is called when PHP is running on the CLI (e.g. via drush),
   * `$settings['file_public_base_url']` must be configured in `settings.php`,
   * otherwise 'default' will be used as the hostname in the new URL.
   *
   * @param string $url
   *   External file URL.
   * @param string $dataset_id
   *   Dataset identifier used to group resources together.
   *
   * @return string|bool
   *   The URL for the newly created file, or FALSE if failure occurs.
   */
   public function saveFile($url, $dataset_id) {

    $targetDir = 'public://distribution/' . $dataset_id;
    $this->fileHelper->prepareDir($targetDir);

    // Abort if file can't be saved locally.
    if (!$path = $this->fileHelper->retrieveFile($url, $targetDir)) {
      return FALSE;
    }

    return $this->fileHelper->fileCreate($path);
  }

}
