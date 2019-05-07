<?php

namespace Drupal\Tests\interra_api\Unit\Service;

use Drupal\interra_api\Service\DatasetModifier;
use Drupal\dkan_common\Tests\DkanTestBase;

/**
 * Tests Drupal\interra_api\Service\DatasetModifier.
 *
 * @coversDefaultClass Drupal\interra_api\Service\DatasetModifier
 * @group interra_api
 */
class DatasetModifierTest extends DkanTestBase {

  public function dataTestModifyDatasetFunctional() {
    return [
        [
            (object) [
                'distribution' => [
                    (object) [
                        'mediaType' => 'text/csv'
                    ],
                    (object) [
                        'mediaType' => 'foobar', // this should be removed.
                    ],
                ]
            ],
            (object) [
                'distribution' => [
                    (object) [
                        'mediaType' => 'text/csv',
                        'format'    => 'csv',
                    ],
                ]
            ],
        ],
        // theme processing
        [
            (object) [
                'distribution'=>[],
                'theme' => [
                    'Foo Bar Theme',
                    'moomootheme'
                ]
            ],
            (object) [
                'distribution'=>[],
                'theme' => [
                    (object) [
                        'identifier' => 'foobartheme',
                        'title'      => 'Foo Bar Theme',
                    ],
                    (object) [
                        'identifier' => 'moomootheme',
                        'title'      => 'moomootheme',
                    ],
                ]
            ],
        ],
        // keyword processing
        [
            (object) [
                'distribution'=>[],
                'keyword' => [
                    'Foo Bar Keyword',
                    'moomookeyword'
                ]
            ],
            (object) [
                'distribution'=>[],
                'keyword' => [
                    (object) [
                        'identifier' => 'foobarkeyword',
                        'title'      => 'Foo Bar Keyword',
                    ],
                    (object) [
                        'identifier' => 'moomookeyword',
                        'title'      => 'moomookeyword',
                    ],
                ]
            ],
        ],
    ];
  }

  /**
   *
   * Tests both modifyDataset() and objectifyStringsArray() to some extent
   *
   * @param \stdClass $dataset
   * @param \stdClass $expected
   *
   * @todo This is pretty much of a functional test.
   * @dataProvider dataTestModifyDatasetFunctional
   */
  public function testModifyDatasetFunctional(\stdClass $dataset, \stdClass $expected) {
    // setup
    $mock = $this->getMockBuilder(DatasetModifier::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

    // assert
    $this->assertEquals($expected, $mock->modifyDataset($dataset));
  }

}
