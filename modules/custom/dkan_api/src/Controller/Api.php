<?php

namespace Drupal\dkan_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sae\Sae;

abstract class Api extends ControllerBase {

  abstract protected function getJsonSchema();

  abstract protected function getStorage();

  public function get($uuid) {

    $storage = $this->getStorage();

    $engine = new Sae($storage, $this->getJsonSchema());

    try {
      $data = $engine->get($uuid);
      return new JsonResponse(json_decode($data));
    }
    catch (\Exception $e) {
      return new JsonResponse((object) ["message" => $e->getMessage()], 404);
    }
  }

  public function post() {
    /* @var $request \Symfony\Component\HttpFoundation\Request */
    $request = \Drupal::request();
    $data = $request->getContent();

    $storage = $this->getStorage();
    $engine = new Sae($storage, $this->getJsonSchema());

    try {
      $id = $engine->post($data);
      $uri = $request->getRequestUri();
      return new JsonResponse((object)["identifier" => "{$uri}/{$id}"]);
    }
    catch (\Exception $e) {
      return new JsonResponse((object) ["message" => $e->getMessage()], 406);
    }
  }
}

