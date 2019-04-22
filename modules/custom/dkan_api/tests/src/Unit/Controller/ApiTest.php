<?php


namespace Drupal\Tests\dkan_api\Unit\Controller;

use Drupal\dkan_api\Controller\Api;
use Drupal\dkan_api\Storage\DrupalNodeDataset;
use Dkan\Tests\DkanTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of OrganizationTest
 * @coversDefaultClass \Drupal\dkan_api\Controller\Api
 * @group dkan_api
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
class ApiTest extends DkanTestBase {

  public function testConstruct() {
    
    $mockContainer = $this->createMock(ContainerInterface::class);
    
    $mock = $this->getMockBuilder(Api::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    
    // assert
    $mock->__construct($mockContainer);
    
    $this->assertSame(
            $mockContainer,
            $this->readAttribute($mock, 'container')
    );

  }
  
  public function testGetAll() {


    // setup
    
      // expect
    
      // assert
 }

}
