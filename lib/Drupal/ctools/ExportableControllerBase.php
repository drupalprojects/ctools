<?php

/**
 * @file
 * Definition of Drupal\ctools\ExportableControllerBase.
 */

namespace Drupal\ctools;

/**
 * @todo.
 */
abstract class ExportableControllerBase implements ExportableControllerInterface {
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
  protected $exporter;

  /**
   * @todo.
   */
  public function __construct($type) {
    $this->type = $type;

    // @todo How do we deal with $type not existing? Either an exception or
    // some way to mark the class as unusable.
    $this->info = ctools_exportable_get_info($type);
  }

  /**
   * @todo.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @todo.
   */
  public function getInfo() {
    return $this->info;
  }

  /**
   * @todo.
   */
  public function import($code) {
    // @todo validate the object actually exists.
    return $this->getExporter()->import($code);
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
    return $this->getExporter->export($exportable, $indent);
  }

  /**
   * @todo.
   */
  protected function getExporter() {
    if (empty($this->exporter)) {
      if (class_exists($this->info['exporter class'])) {
        $this->exporter = new $this->info['exporter class']();
      }
      else {
        return FALSE;
      }
    }

    return $this->exporter;
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
