<?php
use Drupal\dkan_data\Storage\Data;
use Drupal\dkan_schema\SchemaRetriever;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ControllerPageTest extends \PHPUnit\Framework\TestCase {

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
      ->will($this->returnCallback(array($this, 'containerGet')));

    return $container;
  }

  public function containerGet($input) {
    switch ($input) {
      case 'dkan_frontend.page':
        $pageBuilder = $this->getMockBuilder(\Drupal\dkan_frontend\Page::class)
          ->disableOriginalConstructor()
          ->setMethods(['build'])
          ->getMock();
        $pageBuilder->method('build')->willReturn("<h1>Hello World!!!</h1>\n");
        return $pageBuilder;
        break;
    }
  }

  public function test() {
    $controller = \Drupal\dkan_frontend\Controller\Page::create($this->getContainer());
    /* @var $response \Symfony\Component\HttpFoundation\Response */
    $response = $controller->page('home');
    $this->assertEquals("<h1>Hello World!!!</h1>\n", $response->getContent());
  }
}
