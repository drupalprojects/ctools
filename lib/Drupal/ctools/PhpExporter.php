<?php

/**
 * @file
 * Definition of Drupal\ctools\PhpExporter.
 */

namespace Drupal\ctools;

/**
 * Defines a ctools exporter which uses PHP to dump the config.
 */
class PhpExporter implements ExporterInterface {

  /**
   * Implements Drupal\ctools\ExporterInterface::export().
   */
  public function import($raw) {
    $data = NULL;
    // Generate some php code that saves the import data to the $data variable.
    $string = '$data = ' . $raw . ';';
    eval($string);
    return $data;
  }

  /**
   * Implements Drupal\ctools\ExporterInterface::export().
   */
  public function export($data) {
    include_once DRUPAL_ROOT . '/core/includes/utility.inc';
    return drupal_var_export($data);
  }
}
