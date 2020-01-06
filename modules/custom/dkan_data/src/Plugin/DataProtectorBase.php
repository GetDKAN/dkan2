<?php

namespace Drupal\dkan_data\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Data protector plugins.
 */
abstract class DataProtectorBase extends PluginBase implements DataProtectorInterface {

  /**
   * List of schemas to potentially protect. Others will be skipped.
   *
   * @var array
   */
  public $protectedSchemas = [
    'dataset',
    'distribution',
  ];

}
