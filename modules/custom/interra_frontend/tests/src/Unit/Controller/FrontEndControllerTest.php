<?php

namespace Drupal\Tests\interra_frontend\Unit\Controller;

use Drupal\interra_frontend\Controller\FrontEndController;
use Drupal\dkan_common\Tests\DkanTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\interra_frontend\InterraPage;
use Drupal\dkan_common\Service\Factory;

/**
 * Tests Drupal\interra_frontend\Controller\FrontEndController.
 *
 * @coversDefaultClass Drupal\interra_frontend\Controller\FrontEndController
 * @group interra_frontend
 */
class FrontEndControllerTest extends DkanTestBase {

  /**
   * Data for testMethodsThatJustCallBuildPage.
   * @return array
   */
  public function dataMethodsThatJustCallBuildPage() {

    return [
        ['about'],
        ['home'],
        ['search'],
        ['api'],
        ['groups'],
        ['org'],
        ['dataset'],
        ['distribution'],
    ];
  }

  /**
   * A bunch of methods just seem to call
   *
   * @dataProvider dataMethodsThatJustCallBuildPage
   * @param string $methodName MethodName
   */
  public function testMethodsThatJustCallBuildPage($methodName) {
    // setup
    $mock = $this->getMockBuilder(FrontEndController::class)
            ->setMethods(['buildPage'])
            ->disableOriginalConstructor()
            ->getMock();

    $mockRequest  = $this->createMock(Request::class);
    $mockResponse = $this->createMock(Response::class);

    // expect
    $mock->expects($this->once())
            ->method('buildPage')
            ->with($mockRequest)
            ->willReturn($mockResponse);

    // assert
    $actual = call_user_func([$mock, $methodName], $mockRequest);
    $this->assertSame($mockResponse, $actual);
  }

  public function testBuildPage() {
    // setup
    $mock = $this->getMockBuilder(FrontEndController::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

    $mockInterraPage = $this->getMockBuilder(InterraPage::class)
            ->setMethods(['build'])
            ->disableOriginalConstructor()
            ->getMock();

    $mockFactory = $this->getMockBuilder(Factory::class)
            ->setMethods(['newHttpResponse'])
            ->disableOriginalConstructor()
            ->getMock();

    $this->setActualContainer([
        'interra_frontend.interra_page' => $mockInterraPage,
        'dkan.factory'                  => $mockFactory,
    ]);

    $mockRequest  = $this->createMock(Request::class);
    $mockResponse = $this->createMock(Response::class);

    $pageContent = '<html>something was built </html>';
    // expect
    $mockInterraPage->expects($this->once())
            ->method('build')
            ->willReturn($pageContent);

    $mockFactory->expects($this->once())
            ->method('newHttpResponse')
            ->with($pageContent)
            ->willReturn($mockResponse);
    // assert
    $actual = $mock->buildPage($mockRequest);
    $this->assertSame($mockResponse, $actual);
  }

}
