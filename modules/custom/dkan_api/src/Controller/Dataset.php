<?php

namespace Drupal\dkan_api\Controller;

use Drupal\dkan_api\Storage\DrupalNodeDataset;

class Dataset extends Api {

  protected function getStorage() {
    return new DrupalNodeDataset();
  }

  protected function getJsonSchema() {
    return "
    {
      \"title\": \"Dataset\",
      \"description\": \"A simple dataset.\",
      \"type\": \"object\",
      \"required\": [
        \"title\"
      ],
      \"properties\": {
        \"title\": {
          \"type\": \"string\",
          \"title\": \"Title\"
        },
        \"identifier\": {
          \"type\": \"string\",
          \"title\": \"Identifier\",
          \"description\": \"Unique identifier for dataset.\"
        },
        \"description\": {
          \"type\": \"string\",
          \"title\": \"Description\"
        },
        \"organization\": {
          \"type\": \"string\",
          \"title\": \"Organization\"
        },
        \"created\": {
          \"type\": \"string\",
          \"title\": \"Created\",
          \"format\": \"date-time\"
        },
        \"modified\": {
          \"type\": \"string\",
          \"title\": \"Modified\",
          \"format\": \"date-time\"
        },
        \"resources\": {
          \"type\": \"array\",
          \"title\": \"Resources\",
          \"items\": {
            \"type\": \"object\",
            \"properties\": {
              \"title\": {
                \"type\": \"string\",
                \"title\": \"Title\"
              },
              \"uri\": {
                \"type\": \"string\",
                \"title\": \"URI\"
              },
              \"type\": {
                \"type\": \"string\",
                \"title\": \"Type\",
                \"enum\": [
                  \"csv\",
                  \"html\",
                  \"xls\"
                ]
              }
            }
          }
        }
      }
    }
    ";
  }
}