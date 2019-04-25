<?php

namespace Drupal\dkan_harvest\Service;

use Drupal\dkan_harvest\EtlWorkerFactory;
use Drupal\dkan_harvest\Harvester;

/**
 * Factory class for bits and pieces of dkan_harvest.
 *
 * Coverage is mostly ignored for this class since it's mostly a proxy to
 * initialise new instances.
 *
 * @codeCoverageIgnore
 */
class Factory {

    /**
   * New instance of Harvester.
   *
   * @param mixed $harvest_plan Harvest plan.
   * @return Harvester Harvester
   */
  public function newHarvester($harvest_plan) {

    return new Harvester($harvest_plan);

  }

  /**
   * New instance of EtlWorkerFactory.
   * 
   * @param mixed $harvest_plan Harvest plan.
   * @return EtlWorkerFactory EtlWorkerFactory
   */
  public function newEtlWorkerFactory($harvest_plan) {
    return new EtlWorkerFactory($harvest_plan);
  }

}
