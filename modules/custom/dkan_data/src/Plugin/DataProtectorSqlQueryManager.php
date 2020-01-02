<?php

namespace Drupal\dkan_data\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class DataProtectorSqlQueryManager extends DefaultPluginManager {

  /**
   * Constructs a new DataProtectorSqlQueryManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/DataProtector/SqlQuery',
      $namespaces,
      $module_handler,
      'Drupal\dkan_data\Plugin\DataProtectorInterface',
      'Drupal\dkan_data\Annotation\DataProtectorSqlQuery'
    );

    $this->alterInfo('dkan_data_data_protector_sql_query_info');
    $this->setCacheBackend($cache_backend, 'dkan_data_data_protector_sql_query_plugins');
  }

}
