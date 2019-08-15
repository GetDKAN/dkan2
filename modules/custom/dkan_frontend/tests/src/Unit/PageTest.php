<?php


class PageTest extends \PHPUnit\Framework\TestCase {

  public function test() {
    $page = new \Drupal\dkan_frontend\Page(__DIR__ . "/../../app");
    $content = $page->build('home');
    $this->assertEquals("<h1>Hello World!!!</h1>\n", $content);

    $content = $page->build('dataset/123');
    $this->assertEquals("<h1>Hello World!!!</h1>\n", $content);
  }

}
