<?php

namespace Drupal\dkan_common\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Class MockChain.
 *
 * @codeCoverageIgnore
 */
class MockChain {

  private $testCase;
  private $definitons = [];
  private $root = NULL;

  /**
   * Constructor.
   */
  public function __construct(TestCase $case) {
    $this->testCase = $case;
  }

  /**
   * Add.
   */
  public function add($objectClass, $method, $return) {
    if (!$this->root) {
      $this->root = $objectClass;
    }
    $this->definitons[$objectClass][$method] = $return;
  }

  /**
   * Get Mock.
   */
  public function getMock() {
    return $this->build($this->root);
  }

  /**
   * Private.
   */
  private function build($objectClass) {
    $methods = $this->getMethods($objectClass);
    $builder = $this->testCase->getMockBuilder($objectClass)
      ->disableOriginalConstructor()
      ->setMethods($methods);

    $mock = $builder->getMockForAbstractClass();
    foreach ($methods as $method) {
      $return = $this->getReturn($objectClass, $method);

      if (is_object($return)) {
        if ($return instanceof \Exception) {
          $mock->method($method)->willThrowException($return);
        }
        else {
          $mock->method($method)->willReturn($return);
        }
      }
      elseif (is_string($return)) {
        if (class_exists($return)) {
          $mock->method($method)->willReturn($this->build($return));
        }
        else {
          $json = json_decode($return);

          if ($json) {
            $mock->method($method)->willReturn($json);
          }
          else {
            $mock->method($method)->willReturn($return);
          }
        }
      }
      elseif (is_array($return)) {
        $mock->method($method)->willReturnCallback(function ($input) use ($return) {
          foreach ($return as $possible_input => $returnObjectClass) {
            if ($input == $possible_input) {
              return $this->build($returnObjectClass);
            }
          }
        });
      }
      else {
        throw new \Exception("Bad definition");
      }
    }
    return $mock;
  }

  /**
   * Private.
   */
  private function getMethods($objectClass) {
    $methods = [];

    if (isset($this->definitons[$objectClass])) {
      foreach ($this->definitons[$objectClass] as $method => $blah) {
        $methods[] = $method;
      }
    }

    return $methods;
  }

  /**
   * Private.
   */
  private function getReturn($objectClass, $method) {
    if (isset($this->definitons[$objectClass][$method])) {
      return $this->definitons[$objectClass][$method];
    }
    return NULL;
  }

}
