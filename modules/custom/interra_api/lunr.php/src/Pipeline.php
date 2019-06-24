<?php

/**
 * @file
 *
 * Creates a simple pipeline to add functions to process tokens.
 */


/**
 * Creates a simple pipeline to process tokens.
 *
 * Example:
 *
 * Create function to process tokens:
 *
 * function trimmer($token) {
    return preg_replace("/[^A-Za-z0-9 ]/", '', $token);
 * }
 *
 * $tokens = ["one", "two!", "three", '$five', "description"];
 * $pipeline = new Pipeline();
 * Add to the pipeline:
 * $pipeline->add('trimmer');
 * $results = $pipeline->run($tokens);
 * var_dump($results);
 *
 * array(5) {
 *  [0]=> string(3) "one"
 *  [1]=> string(4) "two!"
 *  [2]=> string(5) "three"
 *  [3]=> string(4) "five"
 *  [4]=> string(11) "description"
 * }
 *
 */
class Pipeline {

  private $stack = [];

  /**
   * Adds functions to the pipeline.
   *
   * @param string $pipeline Function to add to the pipeline stack. Must be
   * a function that accepts an argument for an array of tokens.
   */
  public function add(string $pipeline) {
    $this->stack[] = $pipeline;
  }

  public function run(array $tokens) {
    $pipelines = $this->stack;
    foreach ($pipelines as $fn) {
      $memo = [];
      foreach ($tokens as $token) {
        $result = $fn($token);
        if ($result) {
          $memo[] = $result;
        }
      }
      $tokens = $memo;
    }
    return $tokens;
  }
}
