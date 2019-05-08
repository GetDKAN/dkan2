<?php

namespace Drupal\Tests\interra_frontend\Unit;

use Drupal\interra_frontend\InterraPage;
use Drupal\dkan_common\Tests\DkanTestBase;
use org\bovigo\vfs\vfsStream;

/**
 * Tests Drupal\interra_frontend\InterraPage.
 *
 * @coversDefaultClass Drupal\interra_frontend\InterraPage
 * @group interra_frontend
 */
class InterraPageTest extends DkanTestBase {

  public function testBuild() {
    // setup
    $mock = $this->getMockBuilder(InterraPage::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

    $expected = '<html>something like that</html>';
    $vfs      = vfsStream::setup('root', null, [
                'data-catalog-frontend' => [
                    'build' => [
                        'index.html' => $expected
                    ],
                ],
    ]);
    $appRoot  = $vfs->url();

    $this->setActualContainer([
        'app.root' => $appRoot,
    ]);
    
    // assert
    $this->assertEquals($expected, $mock->build());
  }

}
