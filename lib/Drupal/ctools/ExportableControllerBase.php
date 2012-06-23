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

  // @todo something has to load this.
  protected $schema = NULL;

  /**
   * @todo.
   */
  protected $exporter;

  /**
   * An array of reserved keys.
   *
   * These will apply to the exportable properties, and the data array that is
   * exported.
   */
  protected $reserved_keys = array(
    'disabled',
    'api_version',
  );

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::__construct().
   */
  public function __construct($type, array $info) {
    $this->type = $type;

    // @todo How do we deal with $type not existing? Either an exception or
    // some way to mark the class as unusable.
    $this->info = $info;
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::getType().
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::getInfo().
   */
  public function getInfo() {
    return $this->info;
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::getSchema().
   */
  public function getSchema() {
    return $this->schema;
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::load().
   */
  public function load($key) {
    $result = $this->loadExportables('keys', array($key));
    if (isset($result[$key])) {
      return $result[$key];
    }
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::loadMultiple().
   */
  public function loadMultiple(array $keys) {
    $results = $this->loadExportables('keys', $keys);

    // Ensure no empty results are returned.
    return array_filter($results);
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::loadAll().
   */
  public function loadAll() {
    return $this->loadExportables('all');
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::import().
   */
  public function import($code) {
    // @todo validate the object actually exists.
    return $this->getExporter()->import($code);
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::enable().
   */
  public function enable(ExportableInterface $exportable) {
    return $this->setStatus($exportable, FALSE);
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::disable().
   */
  public function disable(ExportableInterface $exportable) {
    return $this->setStatus($exportable, TRUE);
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::export().
   */
  public function export(ExportableInterface $exportable, $indent = '') {
    return $this->getExporter()->export($exportable->pack(), $indent);
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::getExporter().
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
   * Implements Drupal\ctools\ExportableControllerInterface::createDuplicate().
   */
  public function createDuplicate(ExportableInterface $exportable) {
    // The cleanest way to duplicate is to export and import which will
    // ensure all IDs are wiped.
    $code = $this->export($exportable);
    $data = $this->import($code);

    return $this->create($data);
  }

  /**
   * Implements Drupal\ctools\ExportableControllerInterface::defaultList().
   */
  function defaultList() {
    $list = array();

    foreach ($this->loadAll() as $exportable) {
      $list[$exportable->id()] = check_plain($exportable->title() . " (" . $exportable->id() . ")");
    }

    return $list;
  }

}
