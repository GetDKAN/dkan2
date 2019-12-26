<?php

declare(strict_types = 1);

namespace Drupal\dkan_data;

/**
 * Interface ModifierInterface.
 *
 * Potentially modify the output of unauthenticated metastore API requests.
 *
 * @package Drupal\dkan_data
 */
interface ModifierInterface {

  /**
   * Modify the output of Metastore's Get.
   *
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   * @param mixed $data
   *   The data whose output is to potentially be modified.
   *
   * @return mixed
   *   The modified data.
   */
  public function modifyGet(string $schema_id, $data);

  /**
   * Modify the output of Metastore's getResources.
   *
   * @param mixed $resources
   *   A dataset's resources.
   *
   * @return mixed
   *   The modified resources.
   */
  public function modifyResources($resources);

  /**
   * Check if running an SQL query is allowed.
   *
   * @return bool
   *   TRUE to allow the SQL Query, FALSE otherwise.
   */
  public function allowSqlQuery() : bool;

}
