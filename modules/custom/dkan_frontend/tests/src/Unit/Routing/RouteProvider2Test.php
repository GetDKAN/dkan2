<?php


class RouteProvider2Test extends \PHPUnit\Framework\TestCase {

  public function test() {
    $provider = new \Drupal\dkan_frontend\Routing\RouteProvider(__DIR__ . "/../../../app");

    /* @var $routes \Symfony\Component\Routing\RouteCollection */
    $routes = $provider->routes();

    /* @var $route \Symfony\Component\Routing\Route */
    /* @var $route \Symfony\Component\Routing\Route */
    foreach ($routes->all() as $route) {
      $this->assertThat(
        $route->getPath(),
        $this->logicalOr(
          $this->equalTo("/dataset/123"),
          $this->equalTo("/home")
        )
      );
    }

  }

}
