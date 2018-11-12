<?php

namespace Drupal\interra_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\dkan_schema\Schema;
use Drupal\interra_api\Interra;
use Drupal\interra_api\Search;
use Drupal\interra_api\Load;
use Drupal\interra_api\SiteMap;
use Drupal\interra_api\Swagger;
use Drupal\interra_api\ApiRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
* An ample controller.
*/
class FrontEndController extends ControllerBase {

  public function about ( Request $request ) {
    return 'hey now';
  }
  public function home ( Request $request ) {
    return 'hey now';
  }
  public function search ( Request $request ) {
    return 'hey now';
  }
  public function groups ( Request $request ) {
    return 'hey now';
  }
  public function org ( Request $request ) {
    return 'hey now';
  }
  public function dataset ( Request $request ) {
    return 'hey now';
  }
  public function distribution ( Request $request ) {
    return 'hey now';
  }

}

