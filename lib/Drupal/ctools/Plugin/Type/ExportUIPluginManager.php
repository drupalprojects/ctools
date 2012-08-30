<?php

  /**
   * @file
   * Definition of Drupal\ctools\Plugin\Type\ExportUIPluginManager.
   */

namespace Drupal\ctools\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

class ExportUIPluginManager extends PluginManagerBase {

  function __construct() {
    $this->discovery = new CacheDecorator(new AnnotatedClassDiscovery('ctools', 'export_ui'), 'ctools:export_ui');
    $this->factory = new DefaultFactory($this->discovery);
  }

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::processDefinition().
   */
  protected function processDefinition(&$definition, $plugin_id) {
    ctools_include('export-ui');
    ctools_export_ui_process($definition, array());
    parent::processDefinition($definition, $plugin_id);
  }

}
