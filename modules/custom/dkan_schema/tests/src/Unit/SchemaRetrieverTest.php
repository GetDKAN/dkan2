<?php

namespace Drupal\Tests\dkan_schema\Unit;

use Drupal\dkan_common\Tests\DkanTestBase;
use Drupal\dkan_schema\SchemaRetriever;
use org\bovigo\vfs\vfsStream;

/**
 * Tests Drupal\dkan_harvest\Extract\DataJson.
 *
 * @coversDefaultClass Drupal\dkan_harvest\Extract\DataJson
 * @group dkan_harvest
 */
class SchemaRetrieverTest extends DkanTestBase {

  /**
   * Tests __construct().
   */
  public function testConstruct() {
    // setup
    $mock = $this->getMockBuilder(SchemaRetriever::class)
            ->setMethods(['findSchemaDirectory'])
            ->disableOriginalConstructor()
            ->getMock();


    // expect
    $mock->expects($this->once())
            ->method('findSchemaDirectory');

    // assert
    $mock->__construct();
  }

  /**
   * Tests getAllIds().
   */
  public function testGetAllIds() {
    // setup
    $mock = $this->getMockBuilder(SchemaRetriever::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
    // assert
    $this->assertEquals($mock->getAllIds(), [
        'dataset',
    ]);
  }

  /**
   * Tests getSchemaDirectory().
   */
  public function testGetSchemaDirectory() {
    // setup
    $mock = $this->getMockBuilder(SchemaRetriever::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

    $expected = '/foo/bar';
    $this->writeProtectedProperty($mock, 'directory', $expected);
    // assert
    $this->assertEquals($expected, $mock->getSchemaDirectory());
  }

  /**
   * Data provider for testRetrieveException.
   * @return array Arguments.
   */
  public function dataTestRetrieveException() {

    return [
        // not valid id
        [
            'foo-id-not-valid',
            [],
            'directory',
            null,
            []
        ],
        // not readable
        [
            'foo-not-readable',
            ['foo-not-readable'],
            'directory',
            null,
            []
        ],
    ];
  }

  /**
   * Tests retrieve() for exception conditions.
   *
   * @dataProvider dataTestRetrieveException
   * 
   * @param string $id
   * @param array $allIds
   * @param string directory
   * @param int $vfsPermissions
   * @param array $vfsStructure filesystem definition as used by vfsstream
   */
  public function testRetrieveException(string $id, array $allIds, string $directory, $vfsPermissions, array $vfsStructure) {
    // setup
    $mock = $this->getMockBuilder(SchemaRetriever::class)
            ->setMethods([
                'getDirectory',
                'getAllIds',
            ])
            ->disableOriginalConstructor()
            ->getMock();

    $vfs = vfsStream::setup('root', $vfsPermissions, $vfsStructure);

    // expect
    $mock->expects($this->once())
            ->method('getDirectory')
            ->willReturn($vfs->url() . '/' . $directory);

    $mock->expects($this->once())
            ->method('getAllIds')
            ->willReturn($allIds);

    $this->setExpectedException(\Exception::class, "Schema {$id} not found.");

    // assert
    $mock->retrieve($id);
  }

  /**
   * Tests retrieve().
   */
  public function testRetrieve() {
    // setup
    $mock = $this->getMockBuilder(SchemaRetriever::class)
            ->setMethods([
                'getDirectory',
                'getAllIds',
            ])
            ->disableOriginalConstructor()
            ->getMock();

    $id = uniqid('id');

    $expected       = '{foobar contents}';
    $allIds         = [$id];
    $vfsPermissions = 0777;
    $vfsStructure   = [
        // need to trim off `/` for vfs
        'foo' => [
            'collections' => [
                "{$id}.json" => $expected,
            ],
        ]
    ];

    $vfs       = vfsStream::setup('root', $vfsPermissions, $vfsStructure);
    $directory = $vfs->url() . '/foo';
    // expect

    $mock->expects($this->once())
            ->method('getDirectory')
            ->willReturn($directory);

    $mock->expects($this->once())
            ->method('getAllIds')
            ->willReturn($allIds);

    // assert

    $actual = $mock->retrieve($id);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests findSchemaDirectory when schema dir is in drupal root.
   */
  public function testFindSchemDirectorySchemaInDrupalRoot() {
    // setup
    $mock = $this->getMockBuilder(SchemaRetriever::class)
            ->setMethods(['getDefaultSchemaDirectory'])
            ->disableOriginalConstructor()
            ->getMock();

    $vfs = vfsStream::setup(uniqid('vfs'), null, [
                'schema' => [],
    ]);

    $this->setActualContainer([
        'app.root' => $vfs->url(),
    ]);

    $expected = $vfs->url() . '/schema';

    // expect
    $mock->expects($this->never())
            ->method('getDefaultSchemaDirectory');

    // assert
    $this->invokeProtectedMethod($mock, 'findSchemaDirectory');
    $this->assertEquals($expected, $this->readAttribute($mock, 'directory'));
  }

  /**
   *  Tests findSchemaDirectory() when using fallback schema.
   */
  public function testFindSchemDirectoryUseDefaultFallback() {
    // setup
    $mock = $this->getMockBuilder(SchemaRetriever::class)
            ->setMethods(['getDefaultSchemaDirectory'])
            ->disableOriginalConstructor()
            ->getMock();

    $vfs = vfsStream::setup(uniqid('vfs'), null, [
                'schema' => [],
    ]);

    $this->setActualContainer([
        'app.root' => uniqid('/foo-this-is-not-valid'),
    ]);

    $expected = $vfs->url();

    // expect
    $mock->expects($this->once())
            ->method('getDefaultSchemaDirectory')
            ->willReturn($expected);

    // assert
    $this->invokeProtectedMethod($mock, 'findSchemaDirectory');
    $this->assertEquals($expected, $this->readAttribute($mock, 'directory'));
  }

  /**
   * Tests findSchemaDirectory() for exception condition.
   */
  public function testFindSchemDirectoryException() {
    // setup
    $mock = $this->getMockBuilder(SchemaRetriever::class)
            ->setMethods(['getDefaultSchemaDirectory'])
            ->disableOriginalConstructor()
            ->getMock();

    $vfs = vfsStream::setup(uniqid('vfs'), null, [
                'schema' => [],
    ]);

    $this->setActualContainer([
        'app.root' => uniqid('/foo-this-is-not-valid'),
    ]);


    $this->setExpectedException(\Exception::class, "No schema directory found.");

    // expect
    $mock->expects($this->once())
            ->method('getDefaultSchemaDirectory')
            ->willReturn(uniqid('/foo-this-is-not-valid-either'));


    // assert
    $this->invokeProtectedMethod($mock, 'findSchemaDirectory');
  }

  /**
   * Tests getDefaultSchemaDirectory().
   */
  public function testGetDefaultSchemaDirectory() {
    $this->markTestSkipped("uses `drupal_get_filename`. harder to mock, so skipping for now.");
  }

}
