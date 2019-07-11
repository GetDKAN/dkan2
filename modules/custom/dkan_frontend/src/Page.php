<?php

namespace Drupal\dkan_frontend;

/**
 *
 */
class Page {

  /**
   *
   * @TODO /data-catalog-frontend/build/index.html may not always exist.
   * @return string|boolean false if file doesn't exist.
   */
  public function build($name) {
    if ($name == 'home') {
      $file = \Drupal::service('app.root') . "/frontend/index.html";
    }
    else {
      $file = \Drupal::service('app.root') . "/frontend/{$name}/index.html";
    }
    return is_file($file) ? file_get_contents($file) : FALSE;
  }

}
