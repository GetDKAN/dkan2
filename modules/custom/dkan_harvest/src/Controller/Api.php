<?php

namespace Drupal\dkan_harvest\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Api.
 *
 * @package Drupal\dkan_harvest\Controller
 */
class Api implements ContainerInjectionInterface {

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
   * Inherited.
   *
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new Api($container);
  }

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
      return $this->exceptionJsonResponse($e);
    }
  }

  /**
   * Get a single harvest plan.
   *
   * @param $identifier
   *   A harvest plan id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getPlan($identifier) {
    try {
      $plan = $this->harvester
        ->getHarvestPlan($identifier);

      return new JsonResponse(
        json_decode($plan),
        200,
        ["Access-Control-Allow-Origin" => "*"]
      );
    }
    catch (\Exception $e) {
      return $this->exceptionJsonResponse($e);
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
      return $this->exceptionJsonResponse($e);
    }
  }

  /**
   * Deregister a harvest.
   */
  public function deregister($identifier) {

    try {

      $this->harvester
        ->deregisterHarvest($identifier);

      return new JsonResponse(
            (object) [
              "identifier" => $identifier,
            ],
            200,
            ["Access-Control-Allow-Origin" => "*"]
      );
    }
    catch (\Exception $e) {
      return $this->exceptionJsonResponse($e);
    }
  }

  /**
   * Runs harvest.
   */
  public function run() {
    try {
      $payloadJson = $this->requestStack->getCurrentRequest()->getContent();
      $payload = json_decode($payloadJson);
      if (!isset($payload->plan_id)) {
        return $this->exceptionJsonResponse(new \Exception("Invalid payload."));
      }

      $id = $payload->plan_id;
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
      return $this->exceptionJsonResponse($e);
    }
  }

  /**
   * Gives list of previous runs for a harvest id.
   */
  public function info() {

    try {
      $id = $this->requestStack->getCurrentRequest()->get('plan');
      if (empty($id)) {
        return new JsonResponse(
          ["message" => "Missing 'plan' query parameter value"],
          400,
          ["Access-Control-Allow-Origin" => "*"]
        );
      }

      $response = array_keys($this->harvester
        ->getAllHarvestRunInfo($id));

      return new JsonResponse(
            $response,
            200,
            ["Access-Control-Allow-Origin" => "*"]
      );
    }
    catch (\Exception $e) {
      return $this->exceptionJsonResponse($e);
    }
  }

  /**
   * Gives information about a single previous harvest run.
   *
   * @param string $identifier
   *   The run's id.
   */
  public function infoRun($identifier) {

    $id = $this->requestStack->getCurrentRequest()->get('plan');
    if (empty($id)) {
      return new JsonResponse(
        ["message" => "Missing 'plan' query parameter value"],
        400,
        ["Access-Control-Allow-Origin" => "*"]
      );
    }

    try {
      $response = $this->harvester
        ->getHarvestRunInfo($id, $identifier);

      return new JsonResponse(
            $response,
            200,
            ["Access-Control-Allow-Origin" => "*"]
      );
    }
    catch (\Exception $e) {
      return $this->exceptionJsonResponse($e);
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

      $result = $this->harvester
        ->revertHarvest($id);

      return new JsonResponse(
            (object) [
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
      return $this->exceptionJsonResponse($e);
    }
  }

  /**
   * Private.
   */
  private function exceptionJsonResponse(\Exception $e) {
    return new JsonResponse(
      (object) [
        'message' => $e->getMessage(),
      ],
      500
    );
  }

}
