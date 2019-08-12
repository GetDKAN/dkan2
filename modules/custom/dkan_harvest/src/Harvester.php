<?php

namespace Drupal\dkan_harvest;

use Contracts\BulkRetriever;
use Contracts\StoreFactoryInterface;
use Harvest\ETL\Factory;
use Harvest\ETL\Factory as EtlFactory;

/**
 * Harvester.
 */
class Harvester {

  private $storeFactory;

  public function __construct(StoreFactoryInterface $storeFactory) {
    $this->storeFactory = $storeFactory;
  }

  /**
   * Get all available harvests.
   *
   * @return array
   *   All ids.
   */
  public function getAllHarvestIds() {
    $store = $this->storeFactory->get("harvest_plans");

    if ($store instanceof BulkRetriever) {
      return array_keys($store->retrieveAll());
    }
    throw new \Exception("The store created by {get_class($this->storeFactory)} does not implement {BulkRetriever::class}");
  }

  /**
   * Register a new harvest plan.
   *
   * @param object $plan
   *   usually an \stdClass representation.
   *
   * @return string
   *   Identifier.
   *
   * @throws \Exception
   *   Exceptions may be thrown if validation fails.
   */
  public function registerHarvest($plan) {

    $this->validateHarvestPlan($plan);

    $store = $this->storeFactory->get("harvest_plans");

    return $store->store(json_encode($plan), $plan->identifier);
  }

  /**
   * Deregister harvest.
   *
   * @param string $id
   *   Id.
   *
   * @return bool
   *   Boolean.
   */
  public function deregisterHarvest(string $id) {
    $this->revertHarvest($id);
    return $this->factory
      ->getPlanStorage()
      ->remove($id);
  }

  /**
   * Public.
   */
  public function revertHarvest($id) {
    return $this->factory
      ->getHarvester($id)
      ->revert();
  }

  /**
   * Public.
   */
  public function runHarvest($id) {
    $plan_store = $this->storeFactory->get("harvest_plans");
    $harvestPlan = json_decode($plan_store->retrieve($id));


    $item_store = $this->storeFactory->get("harvest_{$id}_items");
    $hash_store = $this->storeFactory->get("harvest_{$id}_hashes");
    $harvester = new \Harvest\Harvester(new EtlFactory($harvestPlan, $item_store, $hash_store));

    $result = $harvester->harvest();

    $run_store = $this->storeFactory->get("harvest_{$id}_runs");
    $run_store->store(json_encode($result), "{time()}");

    return $result;
  }

  /**
   * Get Harvest Run Info.
   *
   * @return mixed
   *   FALSE if no matching runID is found.
   */
  public function getHarvestRunInfo($id, $runId) {
    $allRuns = $this->getAllHarvestRunInfo($id);
    return isset($allRuns[$runId]) ? $allRuns[$runId] : FALSE;
  }

  /**
   * Public.
   */
  public function getAllHarvestRunInfo($id) {
    return $this->jsonUtil
      ->decodeArrayOfJson(
          $this->factory
            ->getStorage($id, 'run')
            ->retrieveAll()
    );
  }

  /**
   * Proxy to Etl Factory to validate harvest plan.
   *
   * @param object $plan
   *   Plan.
   *
   * @return bool
   *   Throws exceptions instead of false it seems.
   */
  public function validateHarvestPlan($plan) {
    return Factory::validateHarvestPlan($plan);
  }

}
