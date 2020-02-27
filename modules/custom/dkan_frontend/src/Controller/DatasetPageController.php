<?php

namespace Drupal\dkan_frontend\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * An ample controller.
 */
class DatasetPageController {

  private $pageBuilder;

  /**
   * Controller method.
   */
  public function dataset($identifier) {
    $appRoot = \Drupal::root();
    $file = NULL;
    $node_loaded_by_uuid = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['uuid' => $identifier]);
    $node_loaded_by_uuid = reset($node_loaded_by_uuid);

    if ($node_loaded_by_uuid) {
      $name = str_replace("__", "/", $identifier);
      $file = $appRoot . "/data-catalog-frontend/public/dataset/{$name}/index.html";
      if (empty(file_get_contents($file))) {
        $file = $appRoot . "/data-catalog-frontend/public/dataset/index.html";
      }
    }
    else {
      $file = $appRoot . "/data-catalog-frontend/public/dataset/index.html";
    }

    return Response::create(file_get_contents($file));
  }

}
