<?php

namespace Drupal\dkan_harvest\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Api.
 *
 * @package Drupal\dkan_harvest\Controller
 */
class Api extends ControllerBase {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Harvest.
   *
   * @var \Drupal\dkan_harvest\Harvester
   */
  private $harvester;

  /**
   * Api constructor.
   */
  public function __construct(ContainerInterface $container) {
    $this->requestStack = $container->get('request_stack');
    $this->harvester = $container->get('dkan_harvest.service');
  }

  /**
   * List harvest ids.
   */
  public function index() {

    try {

      $rows = $this->harvester
        ->getAllHarvestIds();

      return new JsonResponse(
            $rows,
            200,
            ["Access-Control-Allow-Origin" => "*"]
      );
    }
    catch (\Exception $e) {
      return new JsonResponse(
            (object) [
              'message' => $e->getMessage(),
            ],
            500
          );
    }
  }

  /**
   * Register a new harvest.
   */
  public function register() {
    try {
      $harvest_plan = $this->requestStack->getCurrentRequest()->getContent();
      $plan = json_decode($harvest_plan);
      $identifier = $this->harvester
        ->registerHarvest($plan);

      return new JsonResponse(
            (object) [
              "identifier" => $identifier,
            ],
            200,
            [
              "Access-Control-Allow-Origin" => "*",
            ]
      );
    }
    catch (\Exception $e) {
      return new JsonResponse(
            (object) [
              'message' => $e->getMessage(),
            ],
            500
          );
    }
  }

  /**
   * Deregister a harvest.
   */
  public function deregister($id) {

    try {

      $this->harvestService
        ->deregisterHarvest($id);

      return $this->dkanFactory
        ->newJsonResponse(
            (object) [
              "endpoint"   => $this->getCurrentRequestUri(),
              "identifier" => $id,
            ],
            200,
            ["Access-Control-Allow-Origin" => "*"]
      );
    }
    catch (\Exception $e) {
      return $this->dkanFactory
        ->newJsonResponse(
            (object) [
              'message' => $e->getMessage(),
            ],
            500
          );
    }
  }

  /**
   * Runs harvest.
   *
   * @param string $id
   *   The harvest id.
   */
  public function run($id) {
    try {

      $result = $this->harvester
        ->runHarvest($id);

      return new JsonResponse(
            (object) [
              "identifier" => $id,
              "result"     => $result,
            ],
            200,
            ["Access-Control-Allow-Origin" => "*"]
      );
    }
    catch (\Exception $e) {
      return new JsonResponse(
            (object) [
              'message' => $e->getMessage(),
            ],
            500
          );
    }
  }

  /**
   * Gives list of previous runs for a harvest id.
   *
   * @param string $id
   *   The harvest id.
   */
  public function info($id) {

    try {

      $response = array_keys($this->harvestService
        ->getAllHarvestRunInfo($id));

      return $this->dkanFactory
        ->newJsonResponse(
            $response,
            200,
            ["Access-Control-Allow-Origin" => "*"]
      );
    }
    catch (\Exception $e) {
      return $this->dkanFactory
        ->newJsonResponse(
            (object) [
              'message' => $e->getMessage(),
            ],
            500
          );
    }
  }

  /**
   * Gives information about a single previous harvest run.
   *
   * @param string $id
   *   The harvest id.
   * @param string $run_id
   *   The run's id.
   */
  public function infoRun($id, $run_id) {

    try {

      $response = $this->harvestService
        ->getHarvestRunInfo($id, $run_id);

      return $this->dkanFactory
        ->newJsonResponse(
            $response,
            200,
            ["Access-Control-Allow-Origin" => "*"]
      );
    }
    catch (\Exception $e) {
      return $this->dkanFactory
        ->newJsonResponse(
            (object) [
              'message' => $e->getMessage(),
            ],
            500
          );
    }
  }

  /**
   * Reverts harvest.
   *
   * @param string $id
   *   The source to revert.
   */
  public function revert($id) {
    try {

      $result = $this->harvestService
        ->revertHarvest($id);

      return $this->dkanFactory
        ->newJsonResponse(
            (object) [
              "endpoint"   => $this->getCurrentRequestUri(),
              "identifier" => $id,
              'result'     => $result,
            ],
            200,
            [
              "Access-Control-Allow-Origin" => "*",
            ]
      );
    }
    catch (\Exception $e) {
      return $this->dkanFactory
        ->newJsonResponse(
            (object) [
              'message' => $e->getMessage(),
            ],
            500
          );
    }
  }

}
