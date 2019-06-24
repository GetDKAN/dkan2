<?php

/**
 * @file
 * Demos for index builder.
 */

include('./src/BuildLunrIndex.php');
include('./src/Pipeline.php');
include('./src/pipelines.php');

// Shows pipeline.
$tokens = ["one", "two ", "the", "three", '$five', "description",];
$pipeline = new Pipeline();
$pipeline->add('trimmer');
$pipeline->add('stop_word_filter');
$pipeline->add('stemmer');
$results = $pipeline->run($tokens);
var_dump($results);

// Shows building index.
$build = new BuildLunrIndex();
$build->ref('identifier');
$build->field("title");
$build->field("description");
$build->addPipeline('trimmer');
$build->addPipeline('stop_word_filter');
$build->addPipeline('stemmer');
$string = file_get_contents("./fixtures/fixture.json");
$datasets = json_decode($string, true);

foreach ($datasets as $dataset) {
  $build->add($dataset);
}

$output = $build->output();
echo json_encode($output, JSON_PRETTY_PRINT);
