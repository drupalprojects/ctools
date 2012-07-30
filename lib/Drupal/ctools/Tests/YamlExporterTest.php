<?php

/**
 * @file
 * Definition of Drupal\ctools\Tests\YamlExporterTest.
 */

namespace Drupal\ctools\Tests;

use Drupal\simpletest\UnitTestBase;
use Drupal\ctools\YamlExporter;
use Drupal\ctools\Tests\ExporterUnitTestBase;

/**
 * Tests for the CTools YAML exporter.
 */
class YamlExporterTest extends ExporterUnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'CTools YAML exporter tests',
      'description' => 'Tests the exporter plugin which uses YAML.',
      'group' => 'Chaos Tools Suite',
    );
  }

  /**
   * Return some test data and expected values.
   *
   * @return array
   *   An array of arrays, which has two keys
   *     - data: The actual data on the php side
   *     - string: The exported string
   */
  protected function exporterData() {
    $data = parent::exporterData();

    $data['number-scalar']['string'] = "1";
    $data['number-string-scalar']['string'] = "'1'";
    $data['boolean-scalar']['string'] = "true";
    $data['string-scalar']['string'] = $data['string-scalar']['data'];
    $data['simple-array']['string'] = "foo: 1\n";

    $data['simple-array-multi']['string'] = "foo: 1
bar: bar
baz: true\n";

    $data['multi-level-array']['string'] = "foo: bar
beatles:
    - Ringo
    - Paul
beatles_complex:
    John:
        job: Singer\n";

    return $data;
  }

  /**
   * Tests Drupal\ctools\YamlExporter::export().
   */
  public function testExport() {
    $exporter = new YamlExporter();

    $test_data = $this->exporterData();
    foreach ($test_data as $info) {
      $export_string = $exporter->export($info['data']);
      $this->assertIdentical($info['string'], $export_string);
    }
  }

  /**
   * Tests Drupal\ctools\YamlExporter::import().
   */
  public function testImport() {
    $exporter = new YamlExporter();

    $test_data = $this->exporterData();
    foreach ($test_data as $info) {
      $data = $exporter->import($info['string']);
      $this->assertIdentical($info['data'], $data);
    }
  }
}

