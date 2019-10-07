<?php

use PHPUnit\Framework\TestCase;
use Procrastinator\Result;

class ImporterListTest extends TestCase
{
  public function test() {

    $options = new \Drupal\dkan_common\Tests\Mock\Options();
    $options->add('total_bytes_copied', 20);
    $options->add('total_bytes', 30);
    $options->add("hello", "hello");

    $fileFetcher = (new \Drupal\dkan_common\Tests\Mock\Chain($this))
      ->add(\FileFetcher\FileFetcher::class, "getStateProperty", $options)
      ->add(\FileFetcher\FileFetcher::class, "getResult", Result::class)
      ->add(Result::class, "getStatus", Result::DONE)
      ->getMock();

    $sequence = new \Drupal\dkan_common\Tests\Mock\Sequence();
    $sequence->add([$fileFetcher]);
    $sequence->add([]);

    $jobStore = (new \Drupal\dkan_common\Tests\Mock\Chain($this))
      ->add(\Drupal\dkan_datastore\Storage\JobStore::class, "retrieveAll", $sequence)
      ->getMock();

    $list = \Drupal\dkan_datastore\Service\ImporterList\ImporterList::getList($jobStore);
    $this->assertTrue(is_array($list));
  }

}
