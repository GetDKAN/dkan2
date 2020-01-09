<?php

namespace Drupal\dkan_common\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Data modifier plugins.
 */
abstract class DataModifierBase extends PluginBase implements DataModifierInterface {

  /**
   * List of schemas to potentially modify. Others will be skipped.
   *
   * @var array
   */
  public $schemasToModify = [
    'dataset',
    'distribution',
  ];

}
