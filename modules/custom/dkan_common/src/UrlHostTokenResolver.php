<?php

namespace Drupal\dkan_common;

/**
 *
 */
class UrlHostTokenResolver {
  const TOKEN = "h-o.st";

  /**
   *
   */
  public static function resolve($string) {
    if (substr_count($string, self::TOKEN) > 0) {
      $string = str_replace(self::TOKEN, \Drupal::request()->getHost(), $string);
    }
    return $string;
  }

}
