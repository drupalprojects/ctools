<?php

/**
 * @file
 * Definition of Drupal\ctools\ExportableControllerBase.
 */

namespace Drupal\ctools;

/**
 * @todo.
 */
abstract class ExportableControllerBase implements ExportableInterface {
  /**
   * @todo.
   */
  protected $type;

  /**
   * @todo.
   */
  protected $info;

  /**
   * @todo.
   */
  public function __construct($type) {
    $this->type = $type;

    // @todo How do we deal with $type not existing? Either an exception or
    // some way to mark the class as unusable.
    $this->info = exportable_get_info($type);
  }

  /**
   * @todo.
   */
  public function getType($exportable) {
    return $this->type;
  }

  /**
   * @todo.
   */
  public function getInfo($exportable) {
    return $this->info;
  }

  /**
   * @todo.
   */
  public function import($code) {
    // @todo validate the object actually exists.
    return $this->info['exporter']->import($code);
  }

  /**
   * @todo.
   */
  public function enable($exportable) {
    return $this->setStatus($exportable, FALSE);
  }

  /**
   * @todo.
   */
  public function disable($exportable) {
    return $this->setStatus($exportable, TRUE);
  }

  /**
   * @todo.
   */
  public function export($exportable, $indent = '') {
    // @todo validate the object actually exists.
    return $this->info['exporter']->export($exportable, $indent);
  }

  /**
   * @todo.
   */
  public function createDuplicate($exportable) {
    // The cleanest way to duplicate is to export and import which will
    // ensure all IDs are wiped.
    $code = $this->export($exportable);
    return $this->import($code);
  }

}
