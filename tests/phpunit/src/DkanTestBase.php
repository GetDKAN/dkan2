<?php

namespace Dkan\PhpUnit;

use Drupal\Tests\UnitTestCase;

class DkanTestBase extends UnitTestCase {

    protected $dkanDirectory;

    /**
     * {@inheritdoc}
     */
    public function setUp() {
        parent::setUp();
        $this->dkanDirectory = realpath(dirname(__FILE__) . '/../../../');
    }

}
