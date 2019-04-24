<?php

namespace Drupal\Tests\dkan_common\Unit\Service;

use Drupal\dkan_common\Tests\DkanTestBase;
use Drupal\dkan_common\Service\Factory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Contracts\Storage as ContractsStorageInterface;
use Sae\Sae;

/**
 * @coversDefaultClass \Drupal\dkan_common\Service\Factory
 * @group dkan_common
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
class FactoryTest extends DkanTestBase {

  /**
   * Tests __constuct().
   */
  public function testConstruct() {
    $mockContainer = $this->createMock(ContainerInterface::class);

    $mock = $this->getMockBuilder(Factory::class)->disableOriginalConstructor()->getMock();

    // Assert.
    $mock->__construct($mockContainer);

    $this->assertSame($mockContainer, $this->readAttribute($mock, 'container'));
  }

  /**
   * Tests newJsonResponse()
   */
  public function testNewJsonResponse() {


    // Setup.
    $mock = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
    // Override nothing.
      ->setMethods(NULL)->getMock();

    // Assert
    // This requires instantiation of an actual JsonResponse class.
    $this->markAsRisky();
    $actual = $mock->newJsonResponse('foo', 500, ['content-type' => 'text/plain'], FALSE);
    $this->assertInstanceOf(JsonResponse::class, $actual);
  }
  
  /**
   * Tests newServiceApiEngine().
   */
  public function testNewServiceApiEngine() {
    // This requires instantiation of an actual Sae class.
    $this->markAsRisky();

    // setup
    $mockContractsStorage = $this->createMock(ContractsStorageInterface::class);
    $dummyJsonSchema = "{}";
            
    /** @var MockObject|Factory $mock */
        $mock = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
    // Override nothing.
      ->setMethods(NULL)->getMock();

      // assert
          // This requires instantiation of an actual JsonResponse class.
    $this->markAsRisky();
    $actual = $mock->newServiceApiEngine($mockContractsStorage, $dummyJsonSchema);
    $this->assertInstanceOf(Sae::class, $actual);
    
      }

}
