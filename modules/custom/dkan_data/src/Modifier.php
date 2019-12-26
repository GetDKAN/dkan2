<?php

declare(strict_types = 1);

namespace Drupal\dkan_data;

/**
 * Class Modifier.
 *
 * @package Drupal\dkan_data
 */
class Modifier implements ModifierInterface {

  /**
   * No changes by default, can be modified with a decorator service.
   *
   * {@inheritDoc}
   */
  public function modifyGet(string $schema_id, $data) {
    return $data;
  }

  /**
   * No changes by default, can be modified with a decorator service.
   *
   * {@inheritDoc}
   */
  public function modifyResources($resources) {
    return $resources;
  }

  /**
   * Allows SQL query by default, can be modified with a decorator service.
   *
   * {@inheritDoc}
   */
  public function allowSqlQuery(): bool {
    return TRUE;
  }

}
