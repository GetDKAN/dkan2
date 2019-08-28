<?php

namespace Drupal\dkan_api\Controller;

use Sae\Sae;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\dkan_data\Storage\Data;
use Drupal\dkan_schema\SchemaRetriever;

/**
 * Class Api.
 */
class Api implements ContainerInjectionInterface {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Storage.
   *
   * @var \Drupal\dkan_data\Storage\Data
   */
  private $storage;

  /**
   * Schema retriever.
   *
   * @var \Drupal\dkan_schema\SchemaRetriever
   */
  private $schemaRetriever;

  /**
   * Inherited.
   *
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new Api($container->get('request_stack'),
      $container->get('dkan_schema.schema_retriever'),
      $container->get('dkan_data.storage')
    );
  }

  /**
   * Constructor.
   */
  public function __construct(RequestStack $requestStack, SchemaRetriever $schemaRetriever, Data $storage) {
    $this->requestStack = $requestStack;
    $this->schemaRetriever = $schemaRetriever;
    $this->storage = $storage;
  }

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

    $datasets = $this->getEngine($schema_id)->get();

    // $datasets is an array of JSON encoded string. Needs to be unflattened.
    $unflattened = array_map(
      function ($json_string) {
          return json_decode($json_string);
      },
      $datasets
    );

    return new JsonResponse(
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

    try {

      $data = $this->getEngine($schema_id)
        ->get($uuid);

      return new JsonResponse(
        json_decode($data),
        200,
        ["Access-Control-Allow-Origin" => "*"]
      );
    }
    catch (\Exception $e) {
      return new JsonResponse((object) ["message" => $e->getMessage()], 404);
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

    /* @var $engine \Sae\Sae */
    $engine = $this->getEngine($schema_id);

    /* @var $request \Symfony\Component\HttpFoundation\Request */
    $uri = $this->requestStack->getCurrentRequest()->getRequestUri();
    $data = $this->requestStack->getCurrentRequest()->getContent();

    // If resource already exists, return HTTP 409 Conflict and existing uri.
    $params = json_decode($data, TRUE);
    if (isset($params['identifier'])) {
      $uuid = $params['identifier'];
      try {
        $this->storage->retrieve($uuid);

        return new JsonResponse(
            (object) ["endpoint" => "{$uri}/{$uuid}"], 409
        );
      }
      catch (\Exception $e) {

      }
    }

    try {
      $uuid = $engine->post($data);
      return new JsonResponse(
          (object) ["endpoint" => "{$uri}/{$uuid}", "identifier" => $uuid],
          201
      );
    }
    catch (\Exception $e) {
      return new JsonResponse((object) ["message" => $e->getMessage()], 406);
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
    /* @var $engine \Sae\Sae */
    $engine = $this->getEngine($schema_id);

    $data = $this->requestStack->getCurrentRequest()->getContent();

    $obj = json_decode($data);
    if (isset($obj->identifier) && $obj->identifier != $uuid) {
      return new JsonResponse((object) ["message" => "Identifier cannot be modified"], 409);
    }

    try {
      $this->storage->retrieve($uuid);
      $engine->put($uuid, $data);
      $uri = $this->requestStack->getCurrentRequest()->getRequestUri();

      // If a new resource is created, inform the user agent via 201 Created.
      return new JsonResponse(
          (object) ["endpoint" => "{$uri}", "identifier" => $uuid],
          200
        );
    }
    catch (\Exception $e) {
      if ($e->getMessage() == "No data with the identifier {$uuid} was found.") {
        try {
          $engine->put($uuid, $data);
          $uri = $this->requestStack->getCurrentRequest()->getRequestUri();

          // If a new resource is created, inform the user agent via 201.
          return new JsonResponse(
            (object) ["endpoint" => "{$uri}", "identifier" => $uuid],
            201
          );
        }
        catch (\Exception $e) {
          return new JsonResponse((object) ["message" => $e->getMessage()], 406);
        }
      }

      return new JsonResponse((object) ["message" => $e->getMessage()], 406);
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

    /* @var $engine \Sae\Sae */
    $engine = $this->getEngine($schema_id);

    $data = $this->requestStack->getCurrentRequest()->getContent();

    $obj = json_decode($data);

    if (!$obj) {
      return new JsonResponse((object) ["message" => "Invalid JSON"], 409);
    }

    if (isset($obj->identifier) && $obj->identifier != $uuid) {
      return new JsonResponse((object) ["message" => "Identifier cannot be modified"], 409);
    }

    try {
      $this->storage->retrieve($uuid);
      $engine->patch($uuid, $data);
      $uri = $this->requestStack->getCurrentRequest()->getRequestUri();
      return new JsonResponse(
          (object) ["endpoint" => "{$uri}", "identifier" => $uuid], 200
      );
    }
    catch (\Exception $e) {
      $status_code = 406;
      if ($e->getMessage() == "No data with the identifier {$uuid} was found.") {
        $status_code = 404;
      }
      return new JsonResponse((object) ["message" => $e->getMessage()], $status_code);
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
    $engine = $this->getEngine($schema_id);

    $engine->delete($uuid);

    return new JsonResponse((object) ["message" => "Dataset {$uuid} has been deleted."], 200);
  }

  /**
   * Get SAE instance.
   *
   * @return \Sae\Sae
   *   Service Api Engine
   */
  public function getEngine($schema_id) {
    return new Sae($this->getStorage($schema_id), $this->getJsonSchema($schema_id));
  }

  /**
   * Get Storage.
   *
   * @return \Drupal\dkan_api\Storage\Data
   *   Dataset
   */
  private function getStorage($schema_id) {
    $this->storage->setSchema($schema_id);
    return $this->storage;
  }

  /**
   * Get Json Schema.
   *
   * @return string
   *   Json schema.
   */
  private function getJsonSchema($schema_id) {

    // @Todo: mechanism to validate against additional schemas. For now,
    // validate against the empty object, as it accepts any valid json.
    if ($schema_id != 'dataset') {
      return '{ }';
    }

    return $this->schemaRetriever->retrieve('dataset');
  }

}
