<?php

namespace Drupal\Tests\dkan_api\Unit\Controller;

use Drupal\dkan_api\Controller\Api;
use Drupal\dkan_common\Tests\DkanTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dkan_common\Service\Factory;

/**
 *
 * @coversDefaultClass \Drupal\dkan_api\Controller\Api
 * @group dkan_api
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
class ApiTest extends DkanTestBase {

  /**
   *
   */
  public function testConstruct() {

    $mock = $this->getMockBuilder(Api::class)
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    
    $mockContainer = $this->getMockContainer();
    
    $mockDkanFactory = $this->createMock(Factory::class);
    
    // expect
    $mockContainer->expects($this->once())
            ->method('get')
            ->with('dkan.factory')
            ->willReturn($mockDkanFactory);

    // Assert.
    $mock->__construct($mockContainer);

    $this->assertSame(
            $mockContainer,
            $this->readAttribute($mock, 'container')
    );
    
    $this->assertSame(
            $mockDkanFactory,
            $this->readAttribute($mock, 'dkanFactory')
    );

  }

  /**
   *
   */
  public function testGetAll() {

    $this->markTestIncomplete('value of $json_string variable seems some review.');

  }

}
