<?php


namespace Drupal\Tests\dkan_api\Unit\Storage;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dkan_common\Tests\DkanTestBase;
use Drupal\dkan_api\Storage\ThemeValueReferencer;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\NodeInterface;

/**
 * Tests Drupal\dkan_api\Storage\ThemeValueReferencer.
 *
 * @coversDefaultClass \Drupal\dkan_api\Storage\ThemeValueReferencer
 * @group dkan_api
 */
class ThemeValueReferencerTest extends DkanTestBase {
  
  public function testConstruct() {
    // setup
    $mock = $this->getMockBuilder(ThemeValueReferencer::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

    $mockEntityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $mockUuidInterface     = $this->createMock(UuidInterface::class);
    $mockQueueFactory      = $this->createMock(QueueFactory::class);

    // assert
    $mock->__construct($mockEntityTypeManager, $mockUuidInterface, $mockQueueFactory);

    $this->assertSame($mockEntityTypeManager, $this->readAttribute($mock, 'entityTypeManager'));
    $this->assertSame($mockUuidInterface, $this->readAttribute($mock, 'uuidService'));
    $this->assertSame($mockQueueFactory, $this->readAttribute($mock, 'queueService'));
  }


  public function dataTestReferenceSingle() {

    $mockNode = $this->createMock(NodeInterface::class);
        $expected = uniqid('a-uuid');
      $mockNode->uuid = (object) ['value' => $expected];

    return [
        ['foobar', [$mockNode], $expected],
        ['barfoo', [], NULL],
    ];

  }

  /**
   * @dataProvider dataTestReferenceSingle
   *
   * @param string $theme
   * @param array $nodes Array of node objects with uuid->value properties.
   * @param mixed $expected
   */
  public function testReferenceSingle(string $theme, array $nodes, $expected) {
    // setup
    $mock = $this->getMockBuilder(ThemeValueReferencer::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

    $mockEntityTypeManager = $this->getMockBuilder(EntityTypeManagerInterface::class)
            ->setMethods([
                'getStorage'
            ])
            ->getMockForAbstractClass();

    $this->writeProtectedProperty($mock,'entityTypeManager', $mockEntityTypeManager);

    $mockNodeStorage = $this->getMockBuilder(EntityStorageInterface::class)
            ->setMethods([
                'loadByProperties'
            ])
            ->getMockForAbstractClass();

    // expect

    $mockEntityTypeManager->expects($this->once())
            ->method('getStorage')
            ->with('node')
            ->willReturn($mockNodeStorage);

    $mockNodeStorage->expects($this->once())
            ->method('loadByProperties')
            ->with([
                'field_data_type' => "theme",
                'title'           => $theme,
            ])
            ->willReturn($nodes);

    // assert
    $actual = $this->invokeProtectedMethod($mock, 'referenceSingle', $theme);

    $this->assertEquals($expected, $actual);

  }

}
