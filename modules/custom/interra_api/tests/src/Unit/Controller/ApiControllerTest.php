<?php

namespace Drupal\Tests\interra_api\Unit\Controller;

use Drupal\interra_api\Controller\ApiController;
use Drupal\dkan_common\Tests\DkanTestBase;
use JsonSchemaProvider\Provider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\HeaderBag;
use Drupal\dkan_common\Service\Factory;

/**
 * Tests Drupal\interra_api\Controller\ApiController.
 *
 * @coversDefaultClass Drupal\interra_api\Controller\ApiController
 * @group interra_api
 */
class ApiControllerTest extends DkanTestBase {

  /**
   * Tests schemas().
   */
  public function testSchemas() {
    // setup
    $mock = $this->getMockBuilder(ApiController::class)
            ->setMethods([
                'fetchSchema',
                'response',
                'jsonResponse',
            ])
            ->disableOriginalConstructor()
            ->getMock();


    $mockRequest  = $this->createMock(Request::class);
    $mockResponse = $this->createMock(JsonResponse::class);

    $schemaName = 'dataset';
    // cheating a bit
    $schema     = json_encode((object) [
                'foo' => uniqid('bar'),
    ]);

    // expect
    $mock->expects($this->once())
            ->method('fetchSchema')
            ->with($schemaName)
            ->willReturn($schema);

    $mock->expects($this->never())
            ->method('response');

    $mock->expects($this->once())
            ->method('jsonResponse')
            // contains a reference to an stdclass.
            // is a bit iffy since not *same* obbject
            ->with($this->isType('array'))
            ->willReturn($mockResponse);

    // assert
    $actual = $mock->schemas($mockRequest);
    $this->assertSame($mockResponse, $actual);
  }

  /**
   * Tests schemas() on exception.
   */
  public function testSchemasException() {
    // setup
    $mock = $this->getMockBuilder(ApiController::class)
            ->setMethods([
                'fetchSchema',
                'response',
                'jsonResponse',
            ])
            ->disableOriginalConstructor()
            ->getMock();

    $mockRequest  = $this->createMock(Request::class);
    $mockResponse = $this->createMock(JsonResponse::class);

    $schemaName = 'dataset';

    $message = 'something went fubar';

    // expect
    $mock->expects($this->once())
            ->method('fetchSchema')
            ->with($schemaName)
            ->willThrowException(new \Exception($message));

    $mock->expects($this->never())
            ->method('jsonResponse');

    $mock->expects($this->once())
            ->method('response')
            ->with($message)
            ->willReturn($mockResponse);

    // assert
    $actual = $mock->schemas($mockRequest);
    $this->assertSame($mockResponse, $actual);
  }

  /**
   * Tests schema().
   */
  public function testSchema() {
    // setup
    $mock = $this->getMockBuilder(ApiController::class)
            ->setMethods([
                'fetchSchema',
                'response',
                'jsonResponse',
            ])
            ->disableOriginalConstructor()
            ->getMock();

    $mockResponse = $this->createMock(JsonResponse::class);

    $schemaName = uniqid('schema');
    // cheating a bit
    $schema     = json_encode((object) [
                'foo' => uniqid('bar'),
    ]);

    // expect
    $mock->expects($this->once())
            ->method('fetchSchema')
            ->with($schemaName)
            ->willReturn($schema);

    $mock->expects($this->never())
            ->method('response');

    $mock->expects($this->once())
            ->method('jsonResponse')
            ->with(json_decode($schema))
            ->willReturn($mockResponse);

    // assert
    $actual = $mock->schema($schemaName);
    $this->assertSame($mockResponse, $actual);
  }

  /**
   * Tests schema() on exception.
   */
  public function testSchemaException() {
    // setup
    $mock = $this->getMockBuilder(ApiController::class)
            ->setMethods([
                'fetchSchema',
                'response',
                'jsonResponse',
            ])
            ->disableOriginalConstructor()
            ->getMock();


    $mockResponse = $this->createMock(JsonResponse::class);

    $schemaName = uniqid('schema');

    $message = 'something went fubar';

    // expect
    $mock->expects($this->once())
            ->method('fetchSchema')
            ->with($schemaName)
            ->willThrowException(new \Exception($message));

    $mock->expects($this->never())
            ->method('jsonResponse');

    $mock->expects($this->once())
            ->method('response')
            ->with($message)
            ->willReturn($mockResponse);

    // assert
    $actual = $mock->schema($schemaName);
    $this->assertSame($mockResponse, $actual);
  }

  public function testResponse() {
    // setup
    $mock = $this->getMockBuilder(ApiController::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

    $mockFactory = $this->getMockBuilder(Factory::class)
            ->setMethods(['newJsonResponse'])
            ->disableOriginalConstructor()
            ->getMock();

    $this->setActualContainer([
        'dkan.factory' => $mockFactory,
    ]);

    $mockResponse = $this->createMock(JsonResponse::class);

    $mockHeaderBag = $this->getMockBuilder(HeaderBag::class)
            ->setMethods(['set'])
            ->disableOriginalConstructor()
            ->getMock();

    $mockResponse->headers = $mockHeaderBag;

    $resp = uniqid('foo response');

    // expect

    $mockFactory->expects($this->once())
            ->method('newJsonResponse')
            ->with($resp)
            ->willReturn($mockResponse);

    $mockHeaderBag->expects($this->exactly(3))
            ->method('set')
            ->withConsecutive(
                    ['Access-Control-Allow-Origin', '*'],
                    ['Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PATCH, DELETE'],
                    ['Access-Control-Allow-Headers', 'Authorization']
    );

    // assert
    $actual = $this->invokeProtectedMethod($mock, 'response', $resp);
    $this->assertSame($mockResponse, $actual);
  }

  public function testFetchSchema() {
    // setup
    $mock         = $this->getMockBuilder(ApiController::class)
            ->setMethods(['getSchemaProvider'])
            ->disableOriginalConstructor()
            ->getMock();
    $mockProvider = $this->getMockBuilder(Provider::class)
            ->setMethods(['retrieve'])
            ->disableOriginalConstructor()
            ->getMock();

    $schemaName = uniqid('schema_name');
    $schema     = uniqid('the schema itself');

    // expect
    $mock->expects($this->once())
            ->method('getSchemaProvider')
            ->willReturn($mockProvider);

    $mockProvider->expects($this->once())
            ->method('retrieve')
            ->with($schemaName)
            ->willReturn($schema);

    // assert
    $this->assertEquals($schema, $this->invokeProtectedMethod($mock, 'fetchSchema', $schemaName));
  }

  public function testSearch() {
    // setup
    $mock = $this->getMockBuilder(ApiController::class)
            ->setMethods(['response'])
            ->disableOriginalConstructor()
            ->getMock();

    $mockSearch = $this->getMockBuilder(\Drupal\interra_api\Search::class)
            ->setMethods(['index'])
            ->disableOriginalConstructor()
            ->getMock();
    $this->setActualContainer([
        'interra_api.search' => $mockSearch
    ]);

    $mockRequest  = $this->createMock(Request::class);
    $mockResponse = $this->createMock(JsonResponse::class);
    $index        = [uniqid('result of some kind')];

    // expect
    $mockSearch->expects($this->once())
            ->method('index')
            ->willReturn($index);

    $mock->expects($this->once())
            ->method('response')
            ->with($index)
            ->willReturn($mockResponse);

    // assert
    $this->assertSame($mockResponse, $mock->search($mockRequest));
  }

  public function testJsonResponse() {
    // setup
    $mock = $this->getMockBuilder(ApiController::class)
            ->setMethods(['response'])
            ->disableOriginalConstructor()
            ->getMock();

    $mockResponse = $this->createMock(JsonResponse::class);

    $mockHeaderBag = $this->getMockBuilder(HeaderBag::class)
            ->setMethods(['set'])
            ->disableOriginalConstructor()
            ->getMock();

    $mockResponse->headers = $mockHeaderBag;

    $resp = uniqid('foo response');

    // expect

    $mock->expects($this->once())
            ->method('response')
            ->with($resp)
            ->willReturn($mockResponse);

    $mockHeaderBag->expects($this->once())
            ->method('set')
            ->with("Content-Type", "application/schema+json");

    // assert
    $actual = $this->invokeProtectedMethod($mock, 'jsonResponse', $resp);
    $this->assertSame($mockResponse, $actual);
  }

}
