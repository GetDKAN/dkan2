<?php


namespace Drupal\dkan_common\Tests;


class MockChainInputOutput {

  private $inputoutput = [];
  private $use = null;

  public function use($storeId) {
    $this->use = $storeId;
    return $this;
  }

  public function getUse() {
    return $this->use;
  }

  public function addInputOutput($input, $output) {
    $this->inputoutput[$input] = $output;
    return $this;
  }

  public function getInputs() {
    return array_keys($this->inputoutput);
  }

  public function getOutput($input) {
    return $this->inputoutput[$input];
  }
}
