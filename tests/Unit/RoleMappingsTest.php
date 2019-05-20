<?php

namespace Drupal\Tests\uiowa_auth\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\uiowa_auth\RoleMappings;

class RoleMappingsTest extends UnitTestCase
{
  public function testTextToArray() {
    $mappings = 'admin|CN=foo,OU=bar,OU=baz ' . PHP_EOL . ' webmaster|CN=foo,OU=bar' . PHP_EOL . 'webmaster|CN=baz,OU=qux';
    $this->assertEquals(RoleMappings::textToArray($mappings), [
      'admin|CN=foo,OU=bar,OU=baz',
      'webmaster|CN=foo,OU=bar',
      'webmaster|CN=baz,OU=qux',
    ]);
  }

  public function testArrayToText() {
    $mappings = [
      'admin|CN=foo,OU=bar,OU=baz',
      'webmaster|CN=foo,OU=bar',
      'webmaster|CN=baz,OU=qux',
    ];

    $this->assertEquals(RoleMappings::arrayToText($mappings), 'admin|CN=foo,OU=bar,OU=baz' . PHP_EOL . 'webmaster|CN=foo,OU=bar' . PHP_EOL . 'webmaster|CN=baz,OU=qux');
  }

  public function testGenerate() {
    $mappings = [
      'admin|CN=foo,OU=bar,OU=baz',
      'webmaster|CN=foo,OU=bar',
      'webmaster|CN=baz,OU=qux',
    ];

    $expected = [
      [
        'rid' =>'admin',
        'dn' => 'CN=foo,OU=bar,OU=baz'
      ],
      [
        'rid' => 'webmaster',
        'dn' => 'CN=foo,OU=bar'
      ],
      [
        'rid' => 'webmaster',
        'dn' => 'CN=baz,OU=qux',
      ],
    ];

    $i = 0;

    foreach (RoleMappings::generate($mappings) as $rid => $dn) {
      $this->assertEquals($expected[$i]['rid'], $rid);
      $this->assertEquals($expected[$i]['dn'], $dn);
      $i++;
    }
  }
}
