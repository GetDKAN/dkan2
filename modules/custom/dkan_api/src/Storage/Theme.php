<?php

declare(strict_types = 1);

namespace Drupal\dkan_api\Storage;

use Drupal\node\Entity\Node;

/**
 * Handles the replacement of a theme's value with a reference.
 *
 * Also handles basic CRUD operations for themes in datasets.
 */
class Theme {

  /**
   * The array of themes in a dataset.
   *
   * @var array
   */
  private $themes;

  /**
   * Constructs a Theme object.
   *
   * @param array $themes
   *   The array of theme strings.
   */
  public function __construct(array $themes) {
    $this->themes = $themes;
  }

  /**
   * Retrieves or generates identifiers for themes in a dataset.
   *
   * @return array
   *   Array of uuid's for the themes.
   */
  public function retrieveUuids() {
    $uuids = [];
    foreach ($this->themes as $theme) {
      $uuid = $this->retrieveUuid($theme);
      if (!$uuid) {
        $uuid = $this->generateTheme($theme);
      }
      $uuids[] = $uuid;
    }
    return $uuids;
  }

  /**
   * Retrieves a theme's identifier, if it exists.
   *
   * @param string $theme
   *   The theme string.
   *
   * @return mixed
   *   the uuid string or null if not found.
   */
  protected function retrieveUuid(string $theme) : ?string {
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'field_data_type' => "theme",
        'title' => $theme,
      ]);

    foreach ($nodes as $node) {
      return $node->uuid->value;
    }

    return NULL;
  }

  /**
   * Generate a theme data item.
   *
   * @param string $theme
   *   The theme string.
   *
   * @return mixed
   *   the uuid string or null if not found.
   */
  protected function generateTheme(string $theme) : ?string {
    $today = date('Y-m-d');

    // Create theme data, later encoded to json.
    $data = new \StdClass();
    $data->title = $theme;
    $data->identifier = \Drupal::service('uuid')->generate();
    $data->created = $today;
    $data->modified = $today;

    // Create new data node for this theme.
    $node = NODE::create([
      'title' => $theme,
      'type' => 'data',
      'uuid' => $data->identifier,
      'field_data_type' => 'theme',
      'field_json_metadata' => json_encode($data),
    ]);
    $node->save();
    return $node->uuid();
  }

}
