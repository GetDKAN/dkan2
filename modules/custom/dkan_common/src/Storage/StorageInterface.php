<?php

namespace Drupal\dkan_common\Storage;

use Contracts\Storage;
use Contracts\BulkRetriever;

/**
 * Combined interface for storage classes.
 */
interface StorageInterface extends Storage, BulkRetriever {

}
