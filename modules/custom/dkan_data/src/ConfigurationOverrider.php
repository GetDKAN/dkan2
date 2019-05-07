<?php

namespace Drupal\dkan_data;

use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\dkan_schema\SchemaRetriever;

/**
 *
 */
class ConfigurationOverrider implements ConfigFactoryOverrideInterface {

  /**
   *
   */
  public function loadOverrides($names) {
    if (in_array("core.entity_form_display.node.data.default", $names)) {
      $retriever = new SchemaRetriever();
      $schema = $retriever->retrieve("dataset");
      return [
        "core.entity_form_display.node.data.default" =>
        [
          'content' =>
          [
            'field_json_metadata' =>
            [
              'settings' =>
              [
                'json_form' => $schema,
              ],
            ],
          ],
        ],
      ];
    }
    return [];
  }

  /**
   *
   */
  public function getCacheSuffix() {
    $blah = "blah";
  }

  /**
   *
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    $blah = "blah";
  }

  /**
   *
   */
  public function getCacheableMetadata($name) {
    $blah = "blah";
  }

}
