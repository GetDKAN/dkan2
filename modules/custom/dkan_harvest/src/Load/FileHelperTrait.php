<?php

namespace Drupal\dkan_harvest\Load;

/**
 * Helper to wrap drupal filesystem functions.
 *
 * Sideloaded as a trait for convenience.
 */
Trait FileHelperTrait {

  /**
   * 
   * @return IFileHelper;
   */
  protected function getFileHelper() {
    return \Drupal::service('dkan_harvest.file_helper');
  }

}
