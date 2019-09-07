<?php

namespace Drupal\dkan_common\Tests\Mock;

/**
 *
 */
class Options {

  private $options = [];
  private $storeId = NULL;

  /**
   *
   */
  public function use($storeId) {
    $this->storeId = $storeId;
    return $this;
  }

  /**
   *
   */
  public function getUse() {
    return $this->storeId;
  }

  /**
   *
   */
  public function add($option, $return) {
    $this->options[$option] = $return;
    return $this;
  }

  /**
   *
   */
  public function options() {
    return array_keys($this->options);
  }

  /**
   *
   */
  public function return($option) {
    $return = $this->options[$option];

    if ($return instanceof Sequence) {
      $return = $return->return();
    }

    return $return;
  }

}
