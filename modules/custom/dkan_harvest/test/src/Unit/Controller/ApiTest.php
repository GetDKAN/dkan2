<?php

use Harvest\ETL\Load\Simple;
use Harvest\ETL\Extract\DataJson;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Contracts\Mock\Storage\MemoryFactory;
use Drupal\dkan_harvest\Harvester;
use Drupal\dkan_common\Tests\DkanTestBase;
use Drupal\dkan_harvest\Controller\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class ApiTest extends DkanTestBase {

  private $request;

  /**
   *
   */
  public function getContainer() {
    // TODO: Change the autogenerated stub.
    parent::setUp();

    $container = $this->getMockBuilder(ContainerInterface::class)
      ->setMethods(['get'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $container->method('get')
      ->with(
        $this->logicalOr(
          $this->equalTo('dkan_harvest.service'),
          $this->equalTo('dkan_harvest.logger_channel'),
          $this->equalTo('request_stack')
        )
      )
      ->will($this->returnCallback([$this, 'containerGet']));

    return $container;
  }

  /**
   *
   */
  public function containerGet($input) {
    switch ($input) {
      case 'dkan_harvest.service':
        return new Harvester(new MemoryFactory());

      break;
      case 'request_stack':
        $stack = $this->getMockBuilder(RequestStack::class)
          ->disableOriginalConstructor()
          ->setMethods(['getCurrentRequest'])
          ->getMock();

        $stack->method("getCurrentRequest")->willReturn($this->request);

        return $stack;

      break;
    }
  }

  /**
   *
   */
  public function testEmptyIndex() {
    $controller = new Api($this->getContainer());
    $response = $controller->index();
    $this->assertEquals(JsonResponse::class, get_class($response));
    $this->assertEquals($response->getContent(), json_encode([]));
  }

  /**
   *
   */
  public function testBadPlan() {
    $this->request = new Request();
    $controller = new Api($this->getContainer());
    $response = $controller->register();
    $this->assertEquals(JsonResponse::class, get_class($response));
    $this->assertEquals($response->getContent(), json_encode(["message" => "Harvest plan must be a php object."]));
  }

  /**
   *
   */
  public function testRegisterAndIndex() {
    $request = $this->getMockBuilder(Request::class)
      ->setMethods(['getContent'])
      ->disableOriginalConstructor()
      ->getMock();

    $plan = [
      'identifier' => 'test',
      'extract' => ['type' => "blah", "uri" => "http://blah"],
      'load' => ['type' => 'blah'],
    ];

    $request->method('getContent')->willReturn(json_encode($plan));

    $this->request = $request;

    $controller = new Api($this->getContainer());
    $response = $controller->register();
    $this->assertEquals(JsonResponse::class, get_class($response));
    $this->assertEquals($response->getContent(), json_encode(["identifier" => "test"]));

    $response = $controller->index();
    $this->assertEquals(JsonResponse::class, get_class($response));
    $this->assertEquals($response->getContent(), json_encode(["test"]));
  }

  /**
   *
   */
  public function testRegisterAndRunAndInfoAndInfoRunAndRevertAndDeregister() {
    $request = $this->getMockBuilder(Request::class)
      ->setMethods(['getContent'])
      ->disableOriginalConstructor()
      ->getMock();

    $plan = [
      'identifier' => 'test',
      'extract' => ['type' => DataJson::class, "uri" => "file://" . __DIR__ . "/data.json"],
      'load' => ['type' => Simple::class],
    ];

    $request->method('getContent')->willReturn(json_encode($plan));

    $this->request = $request;

    $controller = new Api($this->getContainer());
    $response = $controller->register();
    $this->assertEquals(JsonResponse::class, get_class($response));
    $this->assertEquals($response->getContent(), json_encode(["identifier" => "test"]));

    $response = $controller->run('test');
    $this->assertEquals(JsonResponse::class, get_class($response));
    $result = json_decode($response->getContent())->result;
    $this->assertEquals("NEW", $result->status->load->{"cedcd327-4e5d-43f9-8eb1-c11850fa7c55"});

    $response = $controller->info('test');
    $runs = json_decode($response->getContent());
    $run = array_shift($runs);

    $response = $controller->infoRun("test", $run);
    $result = json_decode($response->getContent());
    $this->assertEquals("NEW", $result->status->load->{"cedcd327-4e5d-43f9-8eb1-c11850fa7c55"});

    $response = $controller->revert('test');
    $content = json_decode($response->getContent());
    $this->assertEquals('test', $content->identifier);
    $this->assertEquals(1, $content->result);

    $response = $controller->deregister('test');
    $content = json_decode($response->getContent());
    $this->assertEquals('test', $content->identifier);
  }

}
