<?php

namespace Drupal\dkan_frontend\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class.
 */
class RouteProvider {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = new RouteCollection();

    $base = \Drupal::service('app.root') . "/data-catalog-frontend/public";
    $possible_pages = $this->expandDirectories($base);

    foreach ($possible_pages as $possible_page) {
      if (file_exists($possible_page . "/index.html")) {
        $name = self::getNameFromPath($possible_page);
        $path = str_replace($base, "", $possible_page);
        $routes->add($name, $this->routeHelper($path, $name));
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
   * Public
   */
  public static function getNameFromPath($path) {
    $base = \Drupal::service('app.root') . "/data-catalog-frontend/public/";
    $sub = str_replace($base, "", $path);
    return str_replace("/", "__", $sub);
  }

  /**
   * Private
   */
  private function expandDirectories($base_dir) {
    $directories = [];
    foreach (scandir($base_dir) as $file) {
      if ($file == '.' || $file == '..') {
        continue;
      }
      $dir = $base_dir . DIRECTORY_SEPARATOR . $file;
      if (is_dir($dir)) {
        $directories[] = $dir;
        $directories = array_merge($directories, $this->expandDirectories($dir));
      }
    }
    return $directories;
  }

  /**
   * @param  string $name
   * @return \Symfony\Component\Routing\Route
   */
  protected function routeHelper(string $path, string $name) : Route {
    $route = new Route(
          "/$path",
          [
            '_controller' => '\Drupal\dkan_frontend\Controller\Page::page',
            'name' => $name,
          ]
      );
    $route->setMethods(['GET']);
    return $route;
  }

}
