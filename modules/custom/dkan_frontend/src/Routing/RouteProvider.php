<?php

namespace Drupal\dkan_frontend\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 *
 */
class RouteProvider {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = new RouteCollection();

    $possible_pages = array_filter(glob(\Drupal::service('app.root') . "/frontend/*"), 'is_dir');

    foreach ($possible_pages as $possible_page) {
      if (file_exists($possible_page ."/index.html")) {
        $name = end(explode('/', $possible_page));
        $routes->add($name, $this->routeHelper($name));
      }
    }

    $route = new Route(
      "/home",
      [
        '_controller' => '\Drupal\dkan_frontend\Controller\Page::page',
        'name' => 'home',
      ]
    );
    $route->setMethods(['GET']);
    $routes->add('home', $route);

    $routes->addRequirements(['_access' => 'TRUE']);

    return $routes;
  }

  /**
   * @param string $name
   * @return Route
   */
  protected function routeHelper(string $name) : Route
  {
    $route = new Route(
      "/{$name}",
      [
        '_controller' => '\Drupal\dkan_frontend\Controller\Page::page',
        'name' => $name,
      ]
    );
    $route->setMethods(['GET']);
    return $route;
  }

}
