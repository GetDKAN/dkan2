<?php

namespace Drupal\dkan_metastore\Exception;

/**
 * Class UnmodifiedObjectException.
 *
 * @package Drupal\dkan_metastore\Exception
 */
class UnmodifiedObjectException extends MetastoreException {

  /**
   * {@inheritdoc}
   */
  public function httpCode(): int {
    return 403;
  }

}
