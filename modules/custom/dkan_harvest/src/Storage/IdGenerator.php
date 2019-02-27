<?php
/**
 * Created by PhpStorm.
 * User: fmizzell
 * Date: 2/22/19
 * Time: 10:24 AM
 */

namespace Drupal\dkan_harvest\Storage;


class IdGenerator implements \Contracts\IdGenerator {

  private $data;

  public function __construct($json) {
    $this->data = json_decode($json);
  }

  public function generate() {
    return isset($this->data->sourceId) ? $this->data->sourceId : NULL;
  }

}