<?php

namespace Drupal\dkan_common;

/**
 * Provides common functionality for calls of data modifier plugins.
 */
trait DataModifierPluginTrait {

  /**
   * Data modifier plugin manager service.
   *
   * @var \Drupal\dkan_common\Plugin\DataModifierManager
   */
  private $pluginManager;

  /**
   * Instances of discovered data modifier plugins.
   *
   * @var array
   */
  private $plugins = [];

  /**
   * Discover data modifier plugins.
   *
   * @return array
   *   A list of discovered data modifier plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function discoverDataModifierPlugins() {
    $plugins = [];
    foreach ($this->pluginManager->getDefinitions() as $definition) {
      $plugins[] = $this->pluginManager->createInstance($definition['id']);
    }
    return $plugins;
  }

}
