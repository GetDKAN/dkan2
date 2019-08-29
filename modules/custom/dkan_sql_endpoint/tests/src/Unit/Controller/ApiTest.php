<?php

namespace Drupal\Tests\dkan_datastore\Unit\Controller;

use Dkan\Datastore\Manager;
use Dkan\Datastore\Resource;
use Drupal\dkan_datastore\Manager\Helper;
use Drupal\dkan_datastore\Storage\Database;
use Drupal\dkan_sql_endpoint\Controller\Api;
use Drupal\dkan_common\Tests\DkanTestBase;
use Drupal\dkan_datastore\Storage\Query;

use SqlParser\SqlParser;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @coversDefaultClass \Drupal\dkan_datastore\Controller\Api
 * @group dkan
 */
class ApiTest extends DkanTestBase {

  public function test() {
    $this->assertTrue(TRUE);
  }

}
