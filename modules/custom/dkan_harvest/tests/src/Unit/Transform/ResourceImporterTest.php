<?php

namespace Drupal\Tests\dkan_harvest\Unit\Transform;

use Dkan\PhpUnit\DkanTestBase;
use Drupal\dkan_harvest\Transform\ResourceImporter;
use Drupal\dkan_harvest\Load\IFileHelper;

/**
 * @group dkan
 */
class ResourceImporterTest extends DkanTestBase {

  /**
   * Data provider for testSaveFile.
   *
   * @return array Array of arguments.
   */
  public function dataTestSaveFile() {
    return [
      ["http://example.com/data1.csv", "data1.csv", TRUE, 'dsid', "http://localhost/site/default/files/distribution/dsid/data1.csv"],
      ["http://example.com/data2.csv", "data2.csv", FALSE, 'dsid', FALSE], // Pass
    ];
  }

  /**
   * Tests the ResourceImporter::saveFile() method.
   *
   * @dataProvider dataTestSaveFile
   *
   * @param string $url
   * @param string $filename
   * @param bool $isUrlValid
   * @param string $datasetId
   * @param string $expected
   */
  public function testSaveFile($url, $filename, $isUrlValid, $datasetId, $expected) {
    // Set up.
    $fileHelperStub = $this->createMock(IFileHelper::class);
    $fileHelperStub->method('prepareDir')
      ->willReturn(TRUE);
    $fileHelperStub->method('retrieveFile')
      ->willReturn($isUrlValid);
    $fileHelperStub->method('fileCreate')
      ->willReturn("http://localhost/site/default/files/distribution/$datasetId/$filename");

    $resourceImporterStub = $this->getMockBuilder(ResourceImporter::class)
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();
    $this->writeProtectedProperty($resourceImporterStub, 'fileHelper', $fileHelperStub);

    // Assert.
    $actual = $resourceImporterStub->saveFile($url, $datasetId);
    $this->assertEquals($expected, $actual);
  }

}
