<?php

use Drupal\dkan_frontend\Controller\DatasetPage as PageController;
use Drupal\dkan_frontend\Page;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class ControllerDatasetPageTest extends TestCase {

   /**
   *
   */
  public function getContainer() {

    $container = $this->getMockBuilder(ContainerInterface::class)
      ->setMethods(['get'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $container->method('get')
      ->with(
        $this->logicalOr(
          $this->equalTo('dkan_frontend.page')
        )
      )
      ->will($this->returnCallback([$this, 'containerGet']));

    return $container;
  }

  /**
   *
   */
  public function containerGet($input) {
    switch ($input) {
      case 'dkan_frontend.page':
        $pageBuilder = $this->getMockBuilder(Page::class)
          ->disableOriginalConstructor()
          ->setMethods(['build'])
          ->getMock();
        $pageBuilder->method('build')->willReturn("<h1>Hello World!!!</h1>\n");
        return $pageBuilder;

      break;
    }
  }

  /**
   *
   */
  public function test() {
    $controller = PageController::create($this->getContainer());
    /* @var $response \Symfony\Component\HttpFoundation\Response */
    $response = $controller->page('/dataset/123');
    $this->assertEquals("<h1>Hello World!!!</h1>\n", $response->getContent());
  }

}
