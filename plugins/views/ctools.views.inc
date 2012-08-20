<?php

/**
 * @file
 * Provide views data and handlers for ctools.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function ctools_views_data() {
  $data = array();
  $data['ctools']['table']['group'] = t('CTools');
  $data['ctools']['table']['join'] = array(
    // #global is a special flag which let's a table appear all the time.
    '#global' => array(),
  );
  $data['ctools']['math'] = array(
    'title' => t('Math expression'),
    'help' => t('Evaluates a mathematical expression and displays it.'),
    'field' => array(
      'id' => 'math',
      'float' => TRUE,
    ),
  );

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function ctools_views_data_alter(&$data) {
  $data['views']['expression']['moved to'] = array('ctools', 'math');
}
