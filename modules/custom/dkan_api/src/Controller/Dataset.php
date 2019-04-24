<?php

namespace Drupal\dkan_api\Controller;

use Drupal\dkan_api\Storage\DrupalNodeDataset;
use Drupal\dkan_schema\SchemaRetriever;
use JsonSchemaProvider\Provider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dataset.
 */
class Dataset extends Api {

  use \Drupal\dkan_common\Util\TestableParentCallTrait;
  
  /**
   *
   * @var DrupalNodeDataset 
   */
  protected $nodeDataset;

  /**
   * {@inheritdocs}
   */
  public function __construct(ContainerInterface $container) {
    $this->parentCall(__FUNCTION__, $container);
    $this->nodeDataset = $container->get('dkan_api.storage.drupal_node_dataset');
  }


/**
 * Get Storage.
 * 
 * @return DrupalNodeDataset Dataset
 */
  protected function getStorage() {
    return $this->nodeDataset;
  }

/**
 * Get Json Schema.
 * 
 * @return string
 */
  protected function getJsonSchema() {

    /** @var Provider $provider */
    $provider = $this->container
            ->get('dkan_schema.json_schema_provider');
    return $provider->retrieve('dataset');
  }

}
