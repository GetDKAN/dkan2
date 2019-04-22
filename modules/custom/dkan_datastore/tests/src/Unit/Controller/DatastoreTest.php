<?php

namespace Drupal\Tests\dkan_datastore\Unit\Controller;

use Drupal\dkan_datastore\Controller\Datastore;
use Dkan\PhpUnit\DkanTestBase;

/**
 * @coversDefaultClass \Drupal\dkan_datastore\Controller\Datastore
 * @group dkan
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
class DatastoreTest extends DkanTestBase {

  use \Dkan\PhpUnit\DkanUnitTestTrait;

  /**
   *
   */
  public function dataTestExplode() {

    return [
    // Invalid but should still pass.
            ['foobar', []],
            [
              '[SELECT * FROM abc];',
                ['SELECT * FROM abc'],
            ],
            [
              '[SELECT * FROM abc][WHERE def LIKE "hij"];',
                [
                  'SELECT * FROM abc',
                  'WHERE def LIKE "hij"',
                ],
            ],
            [
              '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"];',
                [
                  'SELECT * FROM abc',
                  'WHERE def = "hij" AND klm = "nop"',
                ],
            ],
            [
              '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"][ORDER BY qrs];',
                [
                  'SELECT * FROM abc',
                  'WHERE def = "hij" AND klm = "nop"',
                  'ORDER BY qrs ASC',
                ],
            ],
            [
              '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"][ORDER BY qrs, tuv];',
                [
                  'SELECT * FROM abc',
                  'WHERE def = "hij" AND klm = "nop"',
                  'ORDER BY qrs, tuv ASC',
                ],
            ],
            [
              '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"][ORDER BY qrs, tuv DESC][LIMIT 1 OFFSET 2];',
                [
                  'SELECT * FROM abc',
                  'WHERE def = "hij" AND klm = "nop"',
                  'ORDER BY qrs, tuv DESC ASC',
                  'LIMIT 1 OFFSET 2',
                ],
            ],
    ];
  }

  /**
   * Tests explode().
   *
   * @param string $sqlString
   * @param mixed $expected
   *
   * @dataProvider dataTestExplode
   */
  public function testExplode($sqlString, $expected) {

    // Mock with little changed.
    $mock = $this->getMockBuilder(Datastore::class)
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $actual = $this->invokeProtectedMethod($mock, 'explode', $sqlString);

    $this->assertArrayEquals($expected, $actual);
  }

  /**
   *
   */
  public function dataTestGetUuidFromSelect() {
    return [
            // Tests garbage in/out at the same time.
            ['foobar', 'foobar'],
            ['something from foo', 'something from foo'],
            ['something FROM foo', 'foo'],
            ['SELECT something FROM foo WHERE BAR=1', 'foo WHERE BAR=1'],
    ];
  }

  /**
   *
   * @param mixed $select
   * @param mixed $expected
   * @dataProvider dataTestGetUuidFromSelect
   */
  public function testGetUuidFromSelect($select, $expected) {
    // Mock with little changed.
    $mock = $this->getMockBuilder(Datastore::class)
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $actual = $this->invokeProtectedMethod($mock, 'getUuidFromSelect', $select);

    $this->assertEquals($expected, $actual);
  }

}
