<?php

/**
 * @file
 * Definition of Drupal\ctools\ExporterInterface.
 */

namespace Drupal\ctools;

/**
 * Defines how to import and export exportable objects.
 *
 * Each export/import format should define a class that implements this
 * interface to control how export and import should occur.
 */
interface ExporterInterface {

  /**
   * Imports a file and returns data to be loaded into an exportable object.
   *
   * @param string $raw
   *   A raw blob of exported code in the correct format, as provided by the
   *   export() method.
   *
   * @return array
   *   An array of data that can be passed to the constructor of an
   *   ExportableInterface.
   */
  public function import($raw);

  /**
   * Exports a data array to a blob for importing or embedding in a module.
   *
   * @param array $data
   *   An array of data extracted from an ExportableInterface.
   *
   * @return string
   *   A blob of code suitable for use with the paired import() method.
   */
  public function export($data);

}
