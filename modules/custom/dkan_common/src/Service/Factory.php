<?php

namespace Drupal\dkan_common\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sae\Sae;
use Contracts\Storage as ContractsStorageInterface;

/**
 * Factory to generate DKAN API responses.
 */
class Factory implements ContainerInjectionInterface {

  protected $container;

  /**
   * Factory for common DKAN object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Service COntainer.
   */
  public function __construct(ContainerInterface $container) {

    $this->container = $container;
  }

  /**
   * Creates a new json response.
   *
   * @param mixed $data
   *   The response data.
   * @param int $status
   *   The response status code.
   * @param array $headers
   *   An array of response headers.
   * @param bool $json
   *   If the data is already a JSON string.
   */
  public function newJsonResponse($data = NULL, $status = 200, array $headers = [], $json = FALSE) {
    return new JsonResponse($data, $status, $headers, $json);
  }

  /**
   * Creates new ServiceApiEngine.
   *
   * @param \Contracts\Storage $storage
   *   Storage.
   * @param string $jsonSchema
   *   Json Schema.
   *
   * @return \Sae\Sae
   *   New Service Api Engine.
   */
  public function newServiceApiEngine(ContractsStorageInterface $storage, string $jsonSchema) {
    return new Sae($storage, $jsonSchema);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

}
