<?php

namespace Drupal\dkan_data\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Data protector plugins.
 *
 * Plugins of this type may have different conditions and outcomes, but all act
 * on the following publicly accessible API endpoints:
 *   - The metastore's GET collection and GET item
 *   - The dataset-specific Api Docs
 *   - The datastore's SQL query.
 */
interface DataProtectorInterface extends PluginInspectionInterface {

  /**
   * Checks if the schema or data needs protecting.
   *
   * @param string $schema
   *   The schema id.
   * @param mixed $data
   *   The data.
   *
   * @return bool
   *   TRUE if the data requires protection, FALSE otherwise.
   */
  public function requiresProtection(string $schema, $data);

  /**
   * Protects sensitive data by concealing or removing it.
   *
   * @param string $schema
   *   The schema id.
   * @param mixed $data
   *   Contains sensitive data.
   *
   * @return mixed
   *   Free of sensitive data, or FALSE.
   */
  public function protect(string $schema, $data);

}
