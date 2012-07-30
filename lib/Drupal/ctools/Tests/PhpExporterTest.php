<?php

/**
 * @file
 * Definition of Drupal\ctools\Tests\PhpExporterTest.
 */

namespace Drupal\ctools\Tests;

use Drupal\simpletest\UnitTestBase;
use Drupal\ctools\PhpExporter;
use Drupal\ctools\Tests\ExporterUnitTestBase;

/**
 * Tests for the CTools php exporter.
 */
class PhpExporterTest extends ExporterUnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'CTools PHP exporter tests',
      'description' => 'Tests the exporter plugin which uses PHP.',
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
    $data['boolean-scalar']['string'] = "TRUE";
    $data['string-scalar']['string'] = "'" . $data['string-scalar']['data'] . "'";
    $data['simple-array']['string'] = "array(
  'foo' => 1,
)";

    $data['simple-array-multi']['string'] = "array(
  'foo' => 1,
  'bar' => 'bar',
  'baz' => TRUE,
)";

    $data['multi-level-array']['string'] = "array(
  'foo' => 'bar',
  'beatles' => array(
    'Ringo',
    'Paul',
  ),
  'beatles_complex' => array(
    'John' => array(
      'job' => 'Singer',
    ),
  ),
)";

    return $data;
  }

  /**
   * Tests Drupal\ctools\PhpExporter::export().
   */
  public function testExport() {
    $exporter = new PhpExporter();

    $test_data = $this->exporterData();
    foreach ($test_data as $info) {
      $export_string = $exporter->export($info['data']);
      $this->assertIdentical($info['string'], $export_string);
    }
  }

  /**
   * Tests Drupal\ctools\PhpExporter::import().
   */
  public function testImport() {
    $exporter = new PhpExporter();

    $test_data = $this->exporterData();
    foreach ($test_data as $info) {
      $data = $exporter->import($info['string']);
      $this->assertIdentical($info['data'], $data);
    }
  }
}

