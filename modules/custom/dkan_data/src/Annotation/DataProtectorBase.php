<?php

namespace Drupal\dkan_data\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an abstract base class for all data protector plugin annotations.
 *
 * @see plugin_api
 *
 * @Annotation
 */
abstract class DataProtectorBase extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
