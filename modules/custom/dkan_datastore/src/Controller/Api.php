<?php

namespace Drupal\dkan_datastore\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\dkan_datastore\Service\Datastore;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Api.
 *
 * @package Drupal\dkan_datastore\Controller
 * @codeCoverageIgnore
 */
class Api implements ContainerInjectionInterface {
  /**
   * Datastore Service.
   *
   * @var \Drupa\dkan_datastore\Service\Datastore
   */
  protected $datastoreService;

  /**
   * Api constructor.
   */
  public function __construct(Datastore $datastoreService) {
    $this->datastoreService = $datastoreService;
  }

  /**
   * Create controller object from dependency injection container.
   */
  public static function create(ContainerInterface $container) {
    $datastoreService = $container->get('dkan_datastore.service');
    return new Api($datastoreService);
  }

  /**
   * Returns the dataset along with datastore headers and statistics.
   *
   * @param string $uuid
   *   Identifier.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function summary($uuid) {
    try {
      $data = $this->datastoreService->getStorage($uuid)->getSummary();
      return new JsonResponse(
        $data,
        200,
        ["Access-Control-Allow-Origin" => "*"]
      );  
    }
    catch (\Exception $e) {
      return new JsonResponse(
        (object) [
          'message' => $e->getMessage(),
        ],
        404
      );
    }
  }

  /**
   * Import.
   *
   * @param string $uuid
   *   The uuid of a dataset.
   * @param bool $deferred
   *   Whether or not the process should be deferred to a queue.
   */
  public function import($uuid, $deferred = FALSE) {

    try {
      $this->datastoreService->import($uuid, $deferred);

      return $this->dkanFactory
        ->newJsonResponse(
          (object) ["endpoint" => $this->getCurrentRequestUri(), "identifier" => $uuid],
      // Assume always new even if this is a PUT?
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
   * Drop.
   *
   * @param string $uuid
   *   The uuid of a dataset.
   */
  public function delete($uuid) {
    try {
      $this->datastoreService->drop($uuid);
      return $this->dkanFactory
        ->newJsonResponse(
              (object) ["endpoint" => $this->getCurrentRequestUri(), "identifier" => $uuid],
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

}
