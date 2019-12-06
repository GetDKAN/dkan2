<?php

namespace Drupal\dkan_harvest\Transform;

use Harvest\ETL\Transform\Transform;

/**
 * Replaces "&" character with "and" in dataset title, keyword and theme.
 */
class AmpOust extends Transform {
  /**
   * Inherited.
   *
   * {@inheritDoc}
   */
  public function run($item) {
    if ($item->theme) {
      foreach ($item->theme as $key => $value) {
        $item->theme[$key] = str_replace("&", "and", $item->theme[$key]);
      }
    }
    if ($item->keyword) {
      foreach ($item->keyword as $key => $value) {
        $item->keyword[$key] = str_replace("&", "and", $item->keyword[$key]);
      }
    }
    $item->title = str_replace("&", "and", $item->title);
    return $item;
  }

}
