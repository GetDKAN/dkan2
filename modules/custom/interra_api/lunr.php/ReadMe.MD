# Lunr Index Generator for PHP

This project creates an index for Lunr.js in PHP. This will allow you to generate a Lunr.js endpoint in a PHP application.

## Installation

``composer install``

## Tests

Coming soon.

## Usage

Generating an index is similar to Lunr.js.

```php

  // Instantiate the builder.
  $build = new BuildLunrIndex();
  // Add a unique id.
  $build->ref('identifier');
  // Add fields.
  $build->field("title");
  $build->field("description");
  // Add transforms to the pipeline.
  $build->addPipeline('trimmer');
  $build->addPipeline('stop_word_filter');
  $build->addPipeline('stemmer');
  // Load docs.
  $string = file_get_contents("./fixtures/fixture.json");
  $datasets = json_decode($string, true);
  // Add documents to the index.
  foreach ($datasets as $dataset) {
    $build->add($dataset);
  }

  // Output the index.
  $output = $build->output();

  // Place wherever.
  echo json_encode($output, JSON_PRETTY_PRINT);


```

## Pipelines

There is a simple Pipelines class to run transforms on the terms during indexing. See ``src/pipelines.php`` for included pipelines.


## Missing Features

This index is missing boosts and several other indexing features.
