<?php

namespace Drupal\dkan_harvest\Transform;

use Harvest\ETL\Transform\Transform;

/**
 * Class Socrata.
 */
class Socrata extends Transform {

  /**
   * Inherited.
   *
   * {@inheritDoc}
   */
  public function run($item) {

    // Convert URL identifier to just the ID.
    $identifier = $item->identifier;
    $item->identifier = $this->getIdentifier($identifier);

    // Add dummy keyword when keywords are null.
    if (empty($item->keyword)) {
      $item->keyword = ['No keywords provided'];
    }

    // Add dummy description if null.
    if (empty($item->description)) {
      $item->description = 'No description provided';
    }

    // Provide publisher name.
    $publisher = $item->publisher;
    if (!isset($publisher->name) && $publisher->source) {
      $publisher->name = $publisher->source;
    }

    // Add titles for distributions if missing.
    if ($item->distribution) {
      $counter = 0;
      foreach ($item->distribution as $key => $dist) {
        if (empty($dist->title)) {
          $dist->title = "{$identifier}_{$counter}";
          $item->distribution[$key] = $dist;
        }
        $counter++;
      }
    }

    return $item;
  }

  /**
   * Private.
   */
  private function getIdentifier($identifier) {
    $path = parse_url($identifier, PHP_URL_PATH);
    $path = str_replace(['/api/views/', '/datasets/'], ["", ""], $path);
    return $path;
  }

}
