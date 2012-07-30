<?php

/**
 * @file
 * Definition of Drupal\ctools\Tests\ExporterUnitTestBase.
 */

namespace Drupal\ctools\Tests;

use Drupal\simpletest\UnitTestBase;
use Drupal\ctools\PhpExporter;

/**
 * Provides a common foundation for Exporter Tests.
 */
abstract class ExporterUnitTestBase extends UnitTestBase {
  /**
   * Return some test data and expected values.
   *
   * @return array
   *   An array of arrays, which has two keys
   *     - data: The actual data on the php side
   *     - string: The exported string
   */
  protected function exporterData() {
    $data = array();

    // Export a number scalar.
    $data['number-scalar'] = array(
      'data' => 1,
    );

    // Export a number as string scalar.
    $data['number-string-scalar'] = array(
      'data' => '1',
    );

    // Export a boolean scalar.
    $data['boolean-scalar'] = array(
      'data' => TRUE,
    );

    // Export a random string scalar.
    $random_name = $this->randomName();
    $data['string-scalar'] = array(
      'data' => $random_name,
    );

    // Export a simple array with a single key.
    $array_data = array(
      'foo' => 1,
    );
    $data['simple-array'] = array(
      'data' => array('foo' => 1),
    );

    // Export a simple array with multiple keys.
    $data['simple-array-multi'] = array(
      'data' => array(
        'foo' => 1,
        'bar' => 'bar',
        'baz' => TRUE,
      ),
    );

    // Export an array with multiple levels.
    $data['multi-level-array'] = array(
      'data' => array(
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
      ),
    );

    return $data;
  }
}
