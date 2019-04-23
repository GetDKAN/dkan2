<?php

namespace Drupal\Tests\dkan_api\Unit\Service;

use Dkan\Tests\DkanTestBase;
use Drupal\dkan_api\Service\ResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\dkan_api\Service\ResponseFactory
 * @group dkan_api
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
class ResponseFactoryTest extends DkanTestBase {

  /**
   * Tests __constuct().
   */
  public function testConstruct() {
    $mockContainer = $this->createMock(ContainerInterface::class);

    $mock = $this->getMockBuilder(ResponseFactory::class)->disableOriginalConstructor()->getMock();

    // Assert.
    $mock->__construct($mockContainer);

    $this->assertSame($mockContainer, $this->readAttribute($mock, 'container'));
  }

  /**
   * Tests newJsonResponse()
   */
  public function testNewJsonResponse() {
    // This requires instantiation of an actual JsonResponse class.
    $this->markAsRisky();

    // Setup.
    $mock = $this->getMockBuilder(ResponseFactory::class)->disableOriginalConstructor()
    // Override nothing.
      ->setMethods(NULL)->getMock();

    // Assert
    // Test might be incomplete since we dont' really test.
    $actual = $mock->newJsonResponse('foo', 500, ['content-type' => 'text/plain'], FALSE);
    $this->assertInstanceOf(JsonResponse::class, $actual);
  }

}
