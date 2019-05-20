<?php
/**
 * @file
 * A utility class to assist with role mappings.
 */

namespace Drupal\uiowa_auth;

/**
 * Class RoleMappings.
 */
class RoleMappings
{
  /**
   * Static class.
   */
  public function __construct() {}

  /**
   * Convert string of line-break delimited role mappings to array.
   *
   * @param string $mappings
   *   String of mappings delimited by PHP_EOL.
   *
   * @return array
   *   Array of mappings.
   */
  public static function stringToArray($mappings) {
    $mappings = explode(PHP_EOL, $mappings);
    $mappings = array_filter($mappings);
    $mappings = array_map('trim', $mappings);
    return $mappings;
  }

  /**
   * Convert array of role mappings to line-break delimited string.
   *
   * @param $mappings
   * @return string
   */
  public static function arrayToText($mappings) {
    $text = '';

    foreach ($mappings as $mapping) {
      list($rid, $dn) = explode('|', $mapping);
      $text .= "{$rid}|{$dn}";
      $text .= PHP_EOL;
    }

    return rtrim($text);
  }

  /**
   * Generator to yield $rid => $dn from array of role mappings.
   *
   * @param $mappings
   * @return \Generator
   */
  public static function generate($mappings) {
    foreach ($mappings as $mapping) {
      list($rid, $dn) = explode('|', $mapping);
      yield $rid => $dn;
    }
  }

}
