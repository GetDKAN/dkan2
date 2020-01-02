<?php

namespace Drupal\dkan_data\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Data protector plugins.
 */
interface DataProtectorInterface extends PluginInspectionInterface {

  /**
   * Protects potentially sensitive data by concealing or removing it.
   *
   * @param string $schema_id
   *   The schema id.
   * @param string|object $data
   *   A JSON string or object.
   *
   * @return mixed
   *   The modified JSON string or object, revealing less than before.
   */
  public function protect(string $schema_id, $data);

}
