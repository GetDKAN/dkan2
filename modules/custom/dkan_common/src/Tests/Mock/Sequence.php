<?php


namespace Drupal\dkan_common\Tests\Mock;


class Sequence {

  private $sequence = [];
  private $counter = 0;

  public function add($return) {
    $this->sequence[] = $return;

    return $this;
  }

  public function return() {
    $index = $this->counter;

    // Always return the last element when done.
    if (!isset($this->sequence[$index])) {
      $index = count($this->sequence) - 1;
    }

    $return = $this->sequence[$index];
    $this->counter++;
    return $return;
  }

}
