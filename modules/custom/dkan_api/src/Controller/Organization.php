<?php

namespace Drupal\dkan_api\Controller;

class Organization extends Api
{
  protected function getJsonSchema()
  {
    "
    {
      \"title\": \"Organization\",
      \"description\": \"Organization.\",
      \"type\": \"object\",
      \"required\": [
        \"name\"
      ],
      \"properties\": {
        \"name\": {
          \"type\": \"string\",
          \"title\": \"Name\"
        }
      }
    }
    ";
  }

  protected function getStorage()
  {
    return new \Drupal\dkan_api\Storage\Organization();
  }

}