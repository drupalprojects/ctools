<?php

/**
 * @file
 * Definition of Drupal\ctools\ExporterInterface.
 */

namespace Drupal\ctools;

/**
 * @todo.
 */
interface ExporterInterface {

  /**
   * @todo.
   */
  public function import($raw);

  /**
   * @todo.
   */
  public function export($data);

}
