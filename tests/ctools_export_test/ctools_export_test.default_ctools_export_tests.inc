<?php

/**
 * Implements hook_default_export_tests().
 */
function ctools_export_test_default_ctools_export_tests() {
  $ctools_export_tests = array();

  $ctools_export_tests['overridden_test'] = array(
    'disabled' => FALSE,
    'api_version' => 1,
    'machine' => 'overridden_test',
    'title' => 'Overridden test',
    'number' => 1,
    'data' => serialize(array(
    'test_1' => 'Test 1',
    'test_2' => 'Test 2',
    ))
  );

  $ctools_export_tests['default_test'] = array(
    'disabled' => FALSE,
    'api_version' => 1,
    'machine' => 'default_test',
    'title' => 'Default test',
    'number' => 2,
    'data' => serialize(''),
  );

  $ctools_export_tests['default_test_disabled'] = array(
    'disabled' => TRUE,
    'api_version' => 1,
    'machine' => 'default_test_disabled',
    'title' => 'Default test (disabled)',
    'number' => 2,
    'data' => serialize(''),
  );

  return $ctools_export_tests;
}
