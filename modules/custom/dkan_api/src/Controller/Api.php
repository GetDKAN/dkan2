<?php

namespace Drupal\dkan_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sae\Sae;

abstract class Api extends ControllerBase {

  abstract protected function getJsonSchema();

  abstract protected function getStorage();

  public function get($uuid) {

    $engine = $this->getEngine();

    try {
      $data = $engine->get($uuid);
      return new JsonResponse(json_decode($data), 200, ["Access-Control-Allow-Origin" => "*"]);
    }
    catch (\Exception $e) {
      return new JsonResponse((object) ["message" => $e->getMessage()], 404);
    }
  }

  public function postAndGetAll() {
    /* @var $request \Symfony\Component\HttpFoundation\Request */
    $request = \Drupal::request();

    $method = $request->getMethod();

    $engine = $this->getEngine();

    if ($method == "GET") {
      $datasets = $engine->get();
      $json_string = "[" . implode(",", $datasets) . "]";
      return new JsonResponse(json_decode($json_string), 200, ["Access-Control-Allow-Origin" => "*"]);
    }
    elseif ($method == "POST") {

      $data = $request->getContent();

      try {
        $id = $engine->post($data);
        $uri = $request->getRequestUri();
        return new JsonResponse((object)["endpoint" => "{$uri}/{$id}", "identifier" => $id]);
      }
      catch (\Exception $e) {
        return new JsonResponse((object)["message" => $e->getMessage()], 406);
      }
    }
  }

  public function putAndDelete($uuid) {
    /* @var $request \Symfony\Component\HttpFoundation\Request */
    $request = \Drupal::request();

    $method = $request->getMethod();

    /* @var $engine \Sae\Sae */
    $engine = $this->getEngine();

    if ($method == "PUT") {
      $data = $request->getContent();

      try {
        $engine->put($uuid, $data);
        $uri = $request->getRequestUri();
        return new JsonResponse((object)["endpoint" => "{$uri}", "identifier" => $uuid]);
      }
      catch (\Exception $e) {
        return new JsonResponse((object)["message" => $e->getMessage()], 406);
      }
    }
    elseif ($method == "DELETE") {
      $engine->delete($uuid);
      return new JsonResponse((object)["message" => "Dataset {$uuid} has been deleted."], 200);
    }
  }

  public function getEngine() {
    $storage = $this->getStorage();
    return new Sae($storage, $this->getJsonSchema());
  }
}

