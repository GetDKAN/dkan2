<?php


use Drupal\Component\Serialization\Yaml;
use Drupal\interra_api\ApiRequest;
use \PHPUnit\Framework\TestCase;

class ApiRequestTest extends TestCase {

  public function testInstantiateClass() {
    $apiRequest = new ApiRequest();
    $this->assertNotNull($apiRequest);
  }

}

