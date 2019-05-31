<?php

namespace Drupal\dkan_api\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 *
 */
class RouteProvider {

  /**
   * @return array
   *   list of json properties being considered from DKAN json property api
   *   config value.
   */
  public function getPropertyList() {
    $config = \Drupal::config('dkan_data.settings');
    $config_property_list = trim($config->get('property_list'));

    // Trim and split list on newlines whether Windows, MacOS or Linux.
    $property_list = preg_split(
      '/\s*\r\n\s*|\s*\r\s*|\s*\n\s*/',
      $config_property_list,
      -1,
      PREG_SPLIT_NO_EMPTY
    );
    ddl($property_list);
    return $property_list;
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = new RouteCollection();

    $schemas = array_merge(['dataset'], $this->getPropertyList());

    foreach ($schemas as $schema) {
      // GET collection.
      $get_all = new Route("/api/v1/" . $schema);
      $get_all->addDefaults([
        '_controller' => '\Drupal\dkan_api\Controller\Dataset::getAll',
        'schema_id' => $schema,
      ]);
      $get_all->setMethods(['GET']);
      $get_all->setRequirement('_access', 'TRUE');
      $routes->add("dkan_api.{$schema}.get_all", $get_all);

      // GET individual.
      $get = new Route("/api/v1/" . $schema . "/{uuid}");
      $get->addDefaults([
        '_controller' => '\Drupal\dkan_api\Controller\Dataset::get',
        'schema_id' => $schema,
      ]);
      $get->setMethods(['GET']);
      $get->setRequirement('_access', 'TRUE');
      $routes->add("dkan_api.{$schema}.get", $get);

      // POST.
      $post = new Route("/api/v1/" . $schema);
      $post->addDefaults([
        '_controller' => '\Drupal\dkan_api\Controller\Dataset::post',
        'schema_id' => $schema,
      ]);
      $post->setMethods(['POST']);
      $post->setRequirements(['_permission' => 'post put delete datasets through the api']);
      $post->addOptions(['_auth' => ['basic_auth']]);
      $routes->add("dkan_api.{$schema}.post", $post);

      // PUT.
      $put = new Route("/api/v1/" . $schema . "/{uuid}");
      $put->addDefaults([
        '_controller' => '\Drupal\dkan_api\Controller\Dataset::put',
        'schema_id' => $schema,
      ]);
      $put->setMethods(['PUT']);
      $put->addRequirements(['_permission' => 'post put delete datasets through the api']);
      $put->addOptions(['_auth' => ['basic_auth']]);
      $routes->add("dkan_api.{$schema}.put", $put);

      // PATCH.
      $patch = new Route("/api/v1/" . $schema . "/{uuid}");
      $patch->addDefaults([
        '_controller' => '\Drupal\dkan_api\Controller\Dataset::patch',
        'schema_id' => $schema,
      ]);
      $patch->setMethods(['PUT']);
      $patch->addRequirements(['_permission' => 'post put delete datasets through the api']);
      $patch->addOptions(['_auth' => ['basic_auth']]);
      $routes->add("dkan_api.{$schema}.patch", $patch);

      // DELETE.
      $delete = new Route("/api/v1/" . $schema . "/{uuid}");
      $delete->addDefaults([
        '_controller' => '\Drupal\dkan_api\Controller\Dataset::delete',
        'schema_id' => $schema,
      ]);
      $delete->setMethods(['DELETE']);
      $delete->addRequirements(['_permission' => 'post put delete datasets through the api']);
      $delete->addOptions(['_auth' => ['basic_auth']]);
      $routes->add("dkan_api.{$schema}.delete", $delete);
    }

    return $routes;
  }

}
