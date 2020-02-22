<?php

namespace Drupal\dkan_datastore;

use Drupal\dkan_common\AbstractDataNodeLifeCycle;
use Drupal\dkan_common\LoggerTrait;

/**
 * DataNodeLifeCycle.
 */
class DataNodeLifeCycle extends AbstractDataNodeLifeCycle {
  use LoggerTrait;

  /**
   *
   */
  public function insert() {
    $entity = $this->node;
    if ($this->getDataType() != 'distribution') {
      return;
    }

    $metadata = $this->getMetaData();

    if (!isset($metadata->downloadURL) && !isset($metadata->accessURL)) {
      return;
    }

    if ((isset($metadata->mediaType) && $metadata->mediaType == 'text/csv') ||
        (isset($metadata->format) && $metadata->format == 'csv')) {
      try {
        /* @var $datastore_service \Drupal\dkan_datastore\Service */
        $datastore_service = \Drupal::service('dkan_datastore.service');
        $datastore_service->import($entity->uuid(), TRUE);
      }
      catch (\Exception $e) {
        $this->setLoggerFactory(\Drupal::service('logger.factory'));
        $this->log('dkan_datastore', $e->getMessage());
      }
    }

  }

}
