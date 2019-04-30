<?php

use Contracts\IdGenerator;

namespace Drupal\dkan_harvest\Storage;

/**
 *
 */
class IdGenerator implements IdGenerator {

  private $data;

  /**
   *
   */
  public function __construct($json) {
    $this->data = json_decode($json);
  }

  /**
   *
   */
  public function generate() {
    return isset($this->data->identifier) ? $this->data->identifier : NULL;
  }

}
