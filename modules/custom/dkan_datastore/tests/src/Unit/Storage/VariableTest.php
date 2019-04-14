<?php

namespace Drupal\Tests\dkan_datastore\Unit\Storage;

use Dkan\PhpUnit\DkanTestBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\dkan_datastore\Storage\Variable;

/**
 * @coversDefaultClass \Drupal\dkan_datastore\Storage\Variable
 * @group dkan_datastore
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
class VariableTest extends DkanTestBase {

    public function dataTestConstruct() {

        return [
            [['foo'], ['foo']], // successfull getAll
            [FALSE, []], // unsuccessfull getAll
        ];
    }

    /**
     * 
     * @dataProvider dataTestConstruct
     * @param type $getAll
     * @param type $expectedStore
     */
    public function testConstruct($getAll, $expectedStore) {

        $mockConfigInterface = $this->createMock(ConfigFactoryInterface::class);

        $mock = $this->getMockBuilder(Variable::class)
                ->setMethods([
                    'getAll'
                ])
                ->setConstructorArgs([$mockConfigInterface])
                ->getMock();


        $mock->expects($this->once())
                ->method('getAll')
                ->willReturn($getAll);

        //Assert

        $actualConfigFactory = $this->accessProtectedProperty($mock, 'configFactory');
        $actualStore = $this->accessProtectedProperty($mock, 'store');

        $this->assertSame($mockConfigInterface, $actualConfigFactory);
        $this->assertEquals($actualStore, $expectedStore);
    }

}
