<?php

namespace Drupal\Tests\interra_api\Unit\Controller;

use Drupal\interra_api\Search;
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
    // Setup.
    $mock = $this->getMockBuilder(ApiController::class)
      ->setMethods([
        'fetchSchema',
        'response',
        'jsonResponse',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $mockRequest = $this->createMock(Request::class);
    $mockResponse = $this->createMock(JsonResponse::class);

    $schemaName = 'dataset';
    // Cheating a bit.
    $schema = json_encode((object) [
      'foo' => uniqid('bar'),
    ]);

    // Expect.
    $mock->expects($this->once())
      ->method('fetchSchema')
      ->with($schemaName)
      ->willReturn($schema);

    $mock->expects($this->never())
      ->method('response');

    $mock->expects($this->once())
      ->method('jsonResponse')
            // Contains a reference to an stdclass.
            // is a bit iffy since not *same* obbject.
      ->with($this->isType('array'))
      ->willReturn($mockResponse);

    // Assert.
    $actual = $mock->schemas($mockRequest);
    $this->assertSame($mockResponse, $actual);
  }

  /**
   * Tests schemas() on exception.
   */
  public function testSchemasException() {
    // Setup.
    $mock = $this->getMockBuilder(ApiController::class)
      ->setMethods([
        'fetchSchema',
        'response',
        'jsonResponse',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $mockRequest = $this->createMock(Request::class);
    $mockResponse = $this->createMock(JsonResponse::class);

    $schemaName = 'dataset';

    $message = 'something went fubar';

    // Expect.
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

    // Assert.
    $actual = $mock->schemas($mockRequest);
    $this->assertSame($mockResponse, $actual);
  }

  /**
   * Tests schema().
   */
  public function testSchema() {
    // Setup.
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
    // Cheating a bit.
    $schema = json_encode((object) [
      'foo' => uniqid('bar'),
    ]);

    // Expect.
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

    // Assert.
    $actual = $mock->schema($schemaName);
    $this->assertSame($mockResponse, $actual);
  }

  /**
   * Tests schema() on exception.
   */
  public function testSchemaException() {
    // Setup.
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

    // Expect.
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

    // Assert.
    $actual = $mock->schema($schemaName);
    $this->assertSame($mockResponse, $actual);
  }

  /**
   *
   */
  public function testResponse() {
    // Setup.
    $mock = $this->getMockBuilder(ApiController::class)
      ->setMethods(NULL)
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

    // Expect.
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

    // Assert.
    $actual = $this->invokeProtectedMethod($mock, 'response', $resp);
    $this->assertSame($mockResponse, $actual);
  }

  /**
   *
   */
  public function testFetchSchema() {
    // Setup.
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

    // Expect.
    $mock->expects($this->once())
      ->method('getSchemaProvider')
      ->willReturn($mockProvider);

    $mockProvider->expects($this->once())
      ->method('retrieve')
      ->with($schemaName)
      ->willReturn($schema);

    // Assert.
    $this->assertEquals($schema, $this->invokeProtectedMethod($mock, 'fetchSchema', $schemaName));
  }

  /**
   *
   */
  public function testSearch() {
    // Setup.
    $mock = $this->getMockBuilder(ApiController::class)
      ->setMethods(['response'])
      ->disableOriginalConstructor()
      ->getMock();

    $mockSearch = $this->getMockBuilder(Search::class)
      ->setMethods(['index'])
      ->disableOriginalConstructor()
      ->getMock();
    $this->setActualContainer([
      'interra_api.search' => $mockSearch,
    ]);

    $mockRequest  = $this->createMock(Request::class);
    $mockResponse = $this->createMock(JsonResponse::class);
    $index        = [uniqid('result of some kind')];

    // Expect.
    $mockSearch->expects($this->once())
      ->method('index')
      ->willReturn($index);

    $mock->expects($this->once())
      ->method('response')
      ->with($index)
      ->willReturn($mockResponse);

    // Assert.
    $this->assertSame($mockResponse, $mock->search($mockRequest));
  }

  /**
   *
   */
  public function testJsonResponse() {
    // Setup.
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

    // Expect.
    $mock->expects($this->once())
      ->method('response')
      ->with($resp)
      ->willReturn($mockResponse);

    $mockHeaderBag->expects($this->once())
      ->method('set')
      ->with("Content-Type", "application/schema+json");

    // Assert.
    $actual = $this->invokeProtectedMethod($mock, 'jsonResponse', $resp);
    $this->assertSame($mockResponse, $actual);
  }

}
