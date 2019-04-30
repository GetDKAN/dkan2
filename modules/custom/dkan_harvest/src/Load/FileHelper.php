<?php

namespace Drupal\dkan_harvest\Load;

/**
 * @codeCoverageIgnore
 */
class FileHelper implements IFileHelper {

  /**
   *
   */
  public function getRealPath($path) {
    return \Drupal::service('file_system')
      ->realpath($path);
  }

  /**
   *
   */
  public function prepareDir(&$directory) {
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
  }

  /**
   *
   */
  public function retrieveFile($url, $destination = NULL, $managed = FALSE) {
    return system_retrieve_file($url, $destination, $managed, FILE_EXISTS_REPLACE);
  }

  /**
   *
   */
  public function fileCreate($uri) {
    return file_create_url($uri);
  }

  /**
   *
   */
  public function defaultSchemeDirectory() {
    // @todo this might not always work.
    //   Considering s3fs or others that don't live on disk
    return $this->getRealPath(
            \Drupal::config('system.file')
              ->get('default_scheme') . "://"
        );
  }

}
