<?php

namespace Drupal\dkan_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class Api extends ControllerBase {

  /**
   * Represents the data type passed via the HTTP request url schema_id slug.
   *
   * @var string
   */
  protected $schemaId;

  /**
   * Drupal service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Factory to generate various dkan classes.
   *
   * @var \Drupal\dkan_common\Service\Factory
   */
  protected $dkanFactory;

  /**
   * Api constructor.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
    $this->dkanFactory = $container->get('dkan.factory');
  }

  /**
   * Gets the json schema object.
   */
  abstract protected function getJsonSchema();

  /**
   * Gets the storage object.
   */
  abstract protected function getStorage();

  /**
   * Get all.
   *
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function getAll($schema_id) {
    $this->schemaId = $schema_id;

    $datasets = $this->getEngine()
      ->get();

    // $datasets is an array of JSON encoded string. Needs to be unflattened.
    $unflattened = array_map(
        function ($json_string) {
          return json_decode($json_string);
        },
        $datasets
    );

    return $this->dkanFactory
      ->newJsonResponse(
          $unflattened,
          200,
          ["Access-Control-Allow-Origin" => "*"]
      );
  }

  /**
   * Implements GET method.
   *
   * @param string $uuid
   *   Identifier.
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function get($uuid, $schema_id) {
    $this->schemaId = $schema_id;

    try {

      $data = $this->getEngine()
        ->get($uuid);

      return $this->dkanFactory
        ->newJsonResponse(
                json_decode($data),
                200,
                ["Access-Control-Allow-Origin" => "*"]
        );
    }
    catch (\Exception $e) {
      return $this->dkanFactory
        ->newJsonResponse((object) ["message" => $e->getMessage()], 404);
    }
  }

  /**
   * Implements POST method.
   *
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function post($schema_id) {
    $this->schemaId = $schema_id;

    /* @var $engine \Sae\Sae */
    $engine = $this->getEngine();

    /* @var $request \Symfony\Component\HttpFoundation\Request */
    $request = \Drupal::request();
    $uri = $request->getRequestUri();
    $data = $request->getContent();

    // If resource already exists, return HTTP 409 Conflict and existing uri.
    $params = json_decode($data, TRUE);
    if (isset($params['identifier'])) {
      $uuid = $params['identifier'];
      $existing = \Drupal::entityQuery('node')
        ->condition('uuid', $uuid)
        ->execute();
      if ($existing) {
        return $this->dkanFactory
          ->newJsonResponse(
          (object) ["endpoint" => "{$uri}/{$uuid}"], 409);
      }
    }

    try {
      $uuid = $engine->post($data);
      return $this->dkanFactory
        ->newJsonResponse(
        (object) ["endpoint" => "{$uri}/{$uuid}", "identifier" => $uuid],
        201
      );
    }
    catch (\Exception $e) {
      return $this->dkanFactory
        ->newJsonResponse((object) ["message" => $e->getMessage()], 406);
    }
  }

  /**
   * Implements PUT method.
   *
   * @param string $uuid
   *   Identifier.
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function put($uuid, $schema_id) {
    $this->schemaId = $schema_id;

    /* @var $engine \Sae\Sae */
    $engine = $this->getEngine();

    /* @var $request \Symfony\Component\HttpFoundation\Request */
    $request = \Drupal::request();
    $data = $request->getContent();

    $obj = json_decode($data);
    if (isset($obj->identifier) && $obj->identifier != $uuid) {
      return $this->dkanFactory
        ->newJsonResponse((object) ["message" => "Identifier cannot be modified"], 409);
    }

    $existing = \Drupal::entityQuery('node')
      ->condition('uuid', $uuid)
      ->execute();

    try {
      $engine->put($uuid, $data);
      $uri = $request->getRequestUri();
      return $this->dkanFactory
        ->newJsonResponse(
        (object) ["endpoint" => "{$uri}", "identifier" => $uuid],
        // If a new resource is created, inform the user agent via 201 Created.
        empty($existing) ? 201 : 200
      );
    }
    catch (\Exception $e) {
      return $this->dkanFactory
        ->newJsonResponse((object) ["message" => $e->getMessage()], 406);
    }
  }

  /**
   * Implements PATCH method.
   *
   * @param string $uuid
   *   Identifier.
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function patch($uuid, $schema_id) {
    $this->schemaId = $schema_id;

    /* @var $engine \Sae\Sae */
    $engine = $this->getEngine();

    /* @var $request \Symfony\Component\HttpFoundation\Request */
    $request = \Drupal::request();
    $data = $request->getContent();

    $obj = json_decode($data);
    if (isset($obj->identifier) && $obj->identifier != $uuid) {
      return $this->dkanFactory
        ->newJsonResponse((object) ["message" => "Identifier cannot be modified"], 409);
    }

    $existing = \Drupal::entityQuery('node')
      ->condition('uuid', $uuid)
      ->execute();

    if (!$existing) {
      return $this->dkanFactory
        ->newJsonResponse((object) ["message" => "Resource not found"], 404);
    }

    try {
      $engine->patch($uuid, $data);
      $uri = $request->getRequestUri();
      return $this->dkanFactory
        ->newJsonResponse(
        (object) ["endpoint" => "{$uri}", "identifier" => $uuid], 200
      );
    }
    catch (\Exception $e) {
      return $this->dkanFactory
        ->newJsonResponse((object) ["message" => $e->getMessage()], 406);
    }
  }

  /**
   * Implements DELETE method.
   *
   * @param string $uuid
   *   Identifier.
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function delete($uuid, $schema_id) {
    /* @var $engine \Sae\Sae */
    $engine = $this->getEngine();

    $engine->delete($uuid);
    return $this->dkanFactory
      ->newJsonResponse((object) ["message" => "Dataset {$uuid} has been deleted."], 200);
  }

  /**
   * Get SAE instance.
   *
   * @return \Sae\Sae
   *   Service Api Engine
   */
  public function getEngine() {

    return $this->dkanFactory
      ->newServiceApiEngine($this->getStorage(), $this->getJsonSchema());
  }

  /**
   * {@inheritdocs}.
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

}
