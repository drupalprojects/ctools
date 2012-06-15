<?php

/**
 * @file
 * Definition of Drupal\ctools\YamlExporter.
 */

namespace Drupal\ctools;

use Symfony\Component\Yaml\Yaml;

/**
 * Defines a ctools exporter which uses YAML to dump the config.
 */
class YamlExporter implements ExporterInterface {

  /**
   * Implements Drupal\ctools\ExporterInterface::import().
   */
  public function import($raw) {
    return Yaml::parse($raw);
  }

  /**
   * Implements Drupal\ctools\ExporterInterface::export().
   */
  public function export($data) {
    // YAML supports an inline syntax for values, though this is not wanted for
    // several reasons. Set the inline level to the largest value to disable it.
    return Yaml::dump($data, PHP_INT_MAX);
  }
}
