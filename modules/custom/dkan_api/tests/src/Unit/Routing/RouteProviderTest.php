<?php


class RouteProviderTest extends \PHPUnit\Framework\TestCase {
  public function test() {
    $config_factory = $this->getMockBuilder(\Drupal\Core\Config\ConfigFactoryInterface::class)
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMockForAbstractClass();

    $config = $this->getMockBuilder(\Drupal\Core\Config\ImmutableConfig::class)
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMock();

    $config->method('get')->willReturn(['theme']);

    $config_factory->method('get')->willReturn($config);

    $provider = new \Drupal\dkan_api\Routing\RouteProvider($config_factory);

    /* @var $routes \Symfony\Component\Routing\RouteCollection */
    $routes = $provider->routes();

    /* @var $route \Symfony\Component\Routing\Route */
    foreach ($routes->all() as $route) {
      $this->assertThat(
        $route->getPath(),
        $this->logicalOr(
          $this->equalTo("/api/v1/dataset/{uuid}"),
          $this->equalTo("/api/v1/dataset"),
          $this->equalTo("/api/v1/theme/{uuid}"),
          $this->equalTo("/api/v1/theme")
        )
      );
    }
  }

}
