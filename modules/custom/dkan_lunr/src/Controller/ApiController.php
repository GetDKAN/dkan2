<?php

namespace Drupal\dkan_lunr\Controller;

use Dkan\Datastore\Manager;
use Drupal\Core\Controller\ControllerBase;
use JsonSchemaProvider\Provider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\dkan_schema\Schema;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * An ample controller.
 *
 * @codeCoverageIgnore
 */
class ApiController extends ControllerBase {

  /**
   * Public.
   */
  public function search(Request $request) {
    /**
* @var \Drupal\dkan_lunr\Search $search
*/
    $search = \Drupal::service('dkan_lunr.search');
    return $this->response($search->index());
  }

  /**
   * Response.
   *
   * @param mixed $resp
   *   Response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response.
   */
  protected function response($resp) {
    /**
* @var \Symfony\Component\HttpFoundation\JsonResponse $response
*/
    $response = \Drupal::service('dkan.factory')
      ->newJsonResponse($resp);
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PATCH, DELETE');
    $response->headers->set('Access-Control-Allow-Headers', 'Authorization');
    return $response;
  }

}
