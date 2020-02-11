<?php

namespace Drupal\dkan_metastore;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\dkan_common\JsonResponseTrait;
use Drupal\dkan_metastore\Exception\ObjectExists;
use Drupal\dkan_metastore\Exception\ObjectNotFound;
use Drupal\dkan_metastore\Exception\ObjectUnchanged;
use Drupal\dkan_metastore\Exception\InvalidPayload;

/**
 * Class Api.
 *
 * @todo Move docs stuff.
 */
class WebServiceApi implements ContainerInjectionInterface {
  use JsonResponseTrait;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Metastore service.
   *
   * @var \Drupal\dkan_metastore\Service
   */
  private $service;

  /**
   * Inherited.
   *
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new WebServiceApi(
      $container->get('request_stack'),
      $container->get('dkan_metastore.service')
    );
  }

  /**
   * Constructor.
   */
  public function __construct(RequestStack $requestStack, Service $service) {
    $this->requestStack = $requestStack;
    $this->service = $service;
  }

  /**
   * Get schemas.
   */
  public function getSchemas() {
    return $this->getResponse($this->service->getSchemas());
  }

  /**
   * Get schema.
   */
  public function getSchema(string $identifier) {
    return $this->getResponse($this->service->getSchema($identifier));
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
  public function getAll(string $schema_id) {
    return $this->getResponse($this->service->getAll($schema_id));
  }

  /**
   * Implements GET method.
   *
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   * @param string $identifier
   *   Identifier.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function get(string $schema_id, string $identifier) {
    try {
      $object = json_decode($this->service->get($schema_id, $identifier));
      return $this->getResponse($object);
    }
    catch (\Exception $e) {
      return $this->getResponseFromException($e, 404);
    }
  }

  /**
   * GET all resources associated with a dataset.
   *
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   * @param string $identifier
   *   Identifier.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function getResources(string $schema_id, string $identifier) {
    try {
      return $this->getResponse($this->service->getResources($schema_id, $identifier));
    }
    catch (\Exception $e) {
      return $this->getResponseFromException($e, 404);
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
  public function post(string $schema_id) {
    try {
      $data = $this->requestAndCheckData();
      $identifier = $this->service->post($schema_id, $data);
      return $this->getResponse([
        "endpoint" => "{$this->getRequestUri()}/{$identifier}",
        "identifier" => $identifier,
      ], 201);
    }
    catch (InvalidPayload $e) {
      return $this->getResponseFromException($e, $this->getCodeFromInvalidPayloadException($e));
    }
    catch (ObjectExists | \Exception $e) {
      $http_code = [
        ObjectExists::class => 409,
        \Exception::class => 400,
      ];
      return $this->getResponseFromException($e, $http_code[get_class($e)]);
    }
  }

  /**
   * Implements PUT method.
   *
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   * @param string $identifier
   *   Identifier.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function put($schema_id, string $identifier) {
    try {
      $data = $this->requestAndCheckData($identifier);
      $info = $this->service->put($schema_id, $identifier, $data);
      $code = ($info['new'] == TRUE) ? 201 : 200;
      return $this->getResponse(["endpoint" => $this->getRequestUri(), "identifier" => $info['identifier']], $code);
    }
    catch (InvalidPayload $e) {
      return $this->getResponseFromException($e, $this->getCodeFromInvalidPayloadException($e));
    }
    catch (ObjectUnchanged | \Exception $e) {
      $http_code = [
        ObjectUnchanged::class => 403,
        \Exception::class => 400,
      ];
      return $this->getResponseFromException($e, $http_code[get_class($e)]);
    }
  }

  /**
   * Implements PATCH method.
   *
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   * @param string $identifier
   *   Identifier.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function patch($schema_id, $identifier) {
    try {
      $data = $this->requestAndCheckData($identifier);
      $this->service->patch($schema_id, $identifier, $data);
      return $this->getResponse((object) ["endpoint" => $this->getRequestUri(), "identifier" => $identifier]);
    }
    catch (InvalidPayload $e) {
      return $this->getResponseFromException($e, $this->getCodeFromInvalidPayloadException($e));
    }
    catch (ObjectNotFound | ObjectUnchanged | \Exception $e) {
      $http_code = [
        ObjectNotFound::class => 412,
        ObjectUnchanged::class => 403,
        \Exception::class => 400,
      ];
      return $this->getResponseFromException($e, $http_code[get_class($e)]);
    }
  }

  /**
   * Request, check and return the data.
   *
   * @param null|string $identifier
   *   The uuid.
   *
   * @return string
   *   The metadata json string.
   */
  private function requestAndCheckData($identifier = NULL) : string {
    $data = $this->getRequestContent();
    $this->checkData($data, $identifier);
    return $data;
  }

  /**
   * Get http code from invalid payload exception.
   *
   * 400 Bad Request
   * 409 Conflict
   * 415 Unsupported Media Type.
   */
  private function getCodeFromInvalidPayloadException(InvalidPayload $e): int {
    $message = $e->getMessage();
    switch ($message) {
      case "Empty body":
        return 400;

      case "Invalid JSON":
        return 415;

      case "Identifier cannot be modified":
        return 409;
    }
  }

  /**
   * Implements DELETE method.
   *
   * @param string $schema_id
   *   The {schema_id} slug from the HTTP request.
   * @param string $identifier
   *   Identifier.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function delete($schema_id, $identifier) {
    try {
      $this->service->delete($schema_id, $identifier);
      return $this->getResponse((object) ["message" => "Dataset {$identifier} has been deleted."]);
    }
    catch (\Exception $e) {
      return $this->getResponseFromException($e);
    }
  }

  /**
   * Private.
   */
  private function checkData($data, $identifier = NULL) {

    if (empty($data)) {
      throw new InvalidPayload("Empty body");
    }

    $obj = json_decode($data);

    if (!$obj) {
      throw new InvalidPayload("Invalid JSON");
    }

    if (isset($identifier) && isset($obj->identifier) && $obj->identifier != $identifier) {
      throw new InvalidPayload("Identifier cannot be modified");
    }
  }

  /**
   * Get the request's uri.
   *
   * @return string
   *   The uri.
   */
  private function getRequestUri(): string {
    return $this->requestStack->getCurrentRequest()->getRequestUri();
  }

  /**
   * Get the request's content.
   *
   * @return string
   *   The content.
   */
  private function getRequestContent(): string {
    return $this->requestStack->getCurrentRequest()->getContent();
  }

}
