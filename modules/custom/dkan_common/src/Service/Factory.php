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
   * Factory.
   * 
   * @param ContainerInterface $container Service COntainer.
   */
  public function __construct(ContainerInterface $container) {

    $this->container = $container;
  }

  /**
   * Creates a new json response.
   * 
   * @param mixed $data    The response data
   * @param int   $status  The response status code
   * @param array $headers An array of response headers
   * @param bool  $json    If the data is already a JSON string
   */
  public function newJsonResponse($data = null, $status = 200, $headers = [], $json = false) {
    return new JsonResponse($data, $status, $headers, $json);
  }

  /**
   * Creates new ServiceApiEngine
   * 
   * @param ContractsStorageInterface $storage
   * @param string $jsonSchema
   * @return Sae
   */
  public function newServiceApiEngine(ContractsStorageInterface $storage, string $jsonSchema) {
    return new Sae($storage, $jsonSchema);
  }

  /**
   * {@inheritdocs}
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

}
