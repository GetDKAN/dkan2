<?php

declare(strict_types = 1);

namespace Drupal\dkan_data\Service;

use Ramsey\Uuid\Uuid;

/**
 * Service to generate predictable uuid's.
 */
class Uuid5 {

  /**
   * Generate a uuid version 5.
   *
   * @param mixed $value
   *   The value for which we generate a uuid for.
   *
   * @return string
   *   The uuid.
   */
  public function generate($value) {
    if (!is_string($value)) {
      $value = json_encode($value, JSON_UNESCAPED_SLASHES);
    }
    $uuid = Uuid::uuid5(Uuid::NAMESPACE_DNS, $value);
    return $uuid->toString();
  }

}
