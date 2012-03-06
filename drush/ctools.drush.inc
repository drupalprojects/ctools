<?php

/**
 * @file
 * CTools Drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function ctools_drush_command() {
  $items = array();

  $items['ctools-export'] = array(
    'aliases' => array('ctex'),
    'description' => 'Export multiple CTools-handled data objects directly to code.',
    'arguments' => array(
      'module'    => 'Name of your module.',
    ),
    'options' => array(
      'subdir' => 'The name of the sub directory to create the module in. Defaults to ctools_export which will be placed into sites/all/modules.',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'drupal dependencies' => array('bulk_export'),
    'examples' => array(
      'drush ctex export_module' => 'Export CTools exportables to a module called "export_module".',
    ),
  );

  $items['ctools-export-info'] = array(
    'aliases' => array('ctei'),
    'description' => 'Show available CTools exportables.',
    'arguments' => array(),
    'options' => array(
      'format' => 'Display exportables info in a different format such as print_r, json, export. The default is to show in a tabular format.',
      'tables-only' => 'Only show list of exportable types/table names and not available objects.',
      'show-overridden' => 'Only show exportables that have been overridden in the database.',
      'show-disabled' => 'Only show exportables that are currently disabled.',
    ),
    'examples' => array(
      'drush ctools-export-info' => 'View export info on all exportables.',
      'drush ctools-export-info views_view variable' => 'View export info for views_view and variable exportable types only.',
    ),
  );

  $items['ctools-export-revert'] = array(
    'aliases' => array('cter'),
    'description' => 'Revert CTools exportables from changes overridden in the database.',
    'arguments' => array(
      'table name' => 'Base table of the exportable you want to revert.',
      'machine names' => 'Space separated list of exportables you want to revert.',
    ),
    'options' => array(),
    'examples' => array(
      'drush ctools-export-revert views_view' => 'Revert all overridden views exportable objects.',
      'drush ctools-export-revert views_view archive' => 'Revert overridden default archive view.',
    ),
  );

  $items['ctools-export-enable'] = array(
    'aliases' => array('ctee'),
    'description' => 'Enable CTools exportables.',
    'arguments' => array(
      'table name' => 'Base table of the exportable you want to enable.',
      'machine names' => 'Space separated list of exportables you want to enable.',
    ),
    'options' => array(),
    'examples' => array(
      'drush ctools-export-revert views_view' => 'Enable all overridden views exportable objects.',
      'drush ctools-export-revert views_view archive' => 'Enable overridden default archive view.',
    ),
  );

  $items['ctools-export-disable'] = array(
    'aliases' => array('cted'),
    'description' => 'Disable CTools exportables.',
    'arguments' => array(
      'table name' => 'Base table of the exportable you want to disable.',
      'machine names' => 'Space separated list of exportables you want to disable.',
    ),
    'options' => array(),
    'examples' => array(
      'drush ctools-export-revert views_view' => 'Disable all overridden views exportable objects.',
      'drush ctools-export-revert views_view archive' => 'Disable overridden default archive view.',
    ),
  );

  return $items;
}

/**
 * Implementation of hook_drush_help().
 */
function ctools_drush_help($section) {
  switch ($section) {
    case 'meta:ctools:title':
      return dt("CTools commands");
    case 'meta:entity:summary':
      return dt("CTools drush commands.");
  }
}

/**
 * Drush callback: export
 */
function drush_ctools_export($module = 'foo') {
  $error = FALSE;
  if (preg_match('@[^a-z_]+@', $module)) {
    $error = dt('The name of the module must contain only lowercase letters and underscores') . '.';
    drush_log($error, 'error');
    return;
  }

  // Subdirectory.
  $dest_exists = FALSE;
  $subdir = drush_get_option('subdir', 'ctools_export');
  $dest = 'sites/all/modules/' . $subdir . '/' . $module;

  // Check if module or folder exists.
  if (module_exists($module) || file_exists($dest)) {
    $dest_exists = TRUE;
    if (drush_confirm(dt('Are you sure to overwrite files in folder !module ? An extra confirmation will be asked for the module file.', array('!module' => $module)))) {
      drush_log(dt('Files will be overwritten'), 'success');
    }
    else {
      drush_log(dt('Export aborted.'), 'success');
      return;
    }
  }

  // Create new dir if needed.
  if (!$dest_exists) {
    if (!file_exists('sites/all/modules/' . $subdir)) {
      drush_mkdir('sites/all/modules/' . $subdir);
    }
  }

  // Create destination directory.
  drush_mkdir($dest);

  // Create options and call Bulk export function.
  // We create an array, because maybe in the future we can pass in more
  // options to the export function (pre-selected modules and/or exportables).
  $options = array(
    'name' => $module,
  );
  $files = bulk_export_export(TRUE, $options);

  // Start writing.
  if (is_array($files)) {
    foreach ($files as $base_file => $data) {
      $filename = $dest . '/' . $base_file;
      // Extra check for .module file.
      if ($base_file == $module . '.module' && file_exists($filename)) {
        if (!drush_confirm(dt('Do you want to overwrite !module_file', array('!module_file' => $base_file)))) {
          drush_log(dt('Writing of !filename skipped. This is the code that was supposed to be written.', array('!filename' => $filename)), 'success');
          drush_print('---------');
          drush_print($data);
          drush_print('---------');
          continue;
        }
      }
      if (file_put_contents($filename, $data)) {
        drush_log(dt('Succesfully written !filename', array('!filename' => $filename)), 'success');
      }
      else {
        drush_log(dt('Error writing !filename', array('!filename' => $filename)), 'error');
      }
    }
  }
  else {
    drush_log(dt('No files were found to be written.'), 'error');
  }
}

/**
 * Drush callback: Export info.
 *
 * @params $table_names
 *  Each argument will be taken as a CTools exportable table name.
 */
function drush_ctools_export_info() {
  // Collect array of table names from args.
  $table_names = func_get_args();

  // Get format option to allow for alternative output.
  $format = drush_get_option('format');
  $tables_only = drush_get_option('tables-only');
  $show_overridden = drush_get_option('show-overridden', FALSE);
  $show_disabled = drush_get_option('show-disabled', FALSE);
  $load = ($show_overridden || $show_disabled) ? TRUE : FALSE;

  // Get info on these tables, or all if none specified.
  $exportables = _drush_ctools_export_info($table_names, $load);

  if (empty($exportables)) {
    drush_log(dt('There are no exportables available.'), 'warning');
    return;
  }

  /*if ($show_overridden) {
    foreach ($exportables as $table => $objects) {
      foreach ($objects as $key => $object) {
        $overridden = ($object->export_type == 3) ? '*' : NULL;
        $exportables[$table][$key] = $key . $overridden;
      }
    }
  }*/
  if ($show_disabled) {
    foreach ($exportables as $table => $objects) {
      foreach ($objects as $key => $object) {
        if ((!isset($object->disabled)) || ($object->disabled !== TRUE)) {
          unset($exportables[$table][$key]);
        }
      }
    }
  }
  elseif ($show_overridden) {
    foreach ($exportables as $table => $objects) {
      foreach ($objects as $key => $object) {
        if ($object->export_type !== 3) {
          unset($exportables[$table][$key]);
        }
      }
    }
  }

  // Only use array keys if --types-only option is set.
  if ($tables_only) {
    $exportables = array_keys($exportables);
  }

  // Use format from --format
  if ($format) {
    drush_print(drush_format($exportables, $format, NULL));
  }
  // Build a tabular output as default.
  else {
    $header = $tables_only ? array() : array(dt("Base table"), dt("Exportables"));
    $rows = array();
    foreach ($exportables as $table => $info) {
      if (!empty($info)) {
        $row = array(
          $table,
          implode("\n", array_keys($info)) . "\n", // Machine name is better?
        );
        $rows[] = $row;
      }
    }
    array_unshift($rows, $header);
    drush_print_table($rows, TRUE);
  }
}

/**
 * Drush callback: Export revert.
 *
 * @param $table_name
 *  Each argument will be taken as a CTools exportable table name.
 * @param $exportables
 *
 */
function drush_ctools_export_revert() {
   $args = func_get_args();
   // Table name should always be first arg...
   $table_name = array_shift($args);
   // Any additional args are assumed to be exportable names.
   $object_names = $args;

   if (!$table_name) {
    drush_log('Exportable table name empty.', 'error');
    return;
   }

   $info = _drush_ctools_export_info(array($table_name));
   $exportables = $info[$table_name];

   if (!$exportables) {
    drush_log('Exportable table name not found.', 'error');
    return;
   }

   if (empty($args)) {
    $all = drush_confirm(dt('No object names entered. Would you like to revert all exportables of type !type', array('!type' => $table_name)));
    if (!$all) {
      drush_log('Command cancelled', 'success');
      return;
    }
   }
   else {
     foreach (array_keys($exportables) as $exportable) {
      if (!in_array($exportable, $object_names)) {
        unset($exportables[$exportable]);
      }
     }
   }

   //Revert objects
   $objects = ctools_export_crud_load_multiple($table_name, array_keys($exportables));
   foreach ($objects as $object) {
    _drush_ctools_export_revert($table_name, $object);
   }
}

/**
 * Drush callback: Export disable.
 *
 * @param $table_name
 *  Each argument will be taken as a CTools exportable table name.
 * @param $exportables
 *
 */
function drush_ctools_export_disable() {
   $args = func_get_args();
   // Table name should always be first arg...
   $table_name = array_shift($args);
   // Any additional args are assumed to be exportable names.
   $object_names = $args;

   if (!$table_name) {
    drush_log('Exportable table name empty.', 'error');
    return;
   }

   $info = _drush_ctools_export_info(array($table_name));
   $exportables = $info[$table_name];

   if (!$exportables) {
    drush_log('Exportable table name not found.', 'error');
    return;
   }

   if (empty($args)) {
    $all = drush_confirm(dt('No object names entered. Would you like to try and disable all exportables of type !type', array('!type' => $table_name)));
    if (!$all) {
      drush_log('Command cancelled', 'success');
      return;
    }
   }
   else {
     foreach (array_keys($exportables) as $exportable) {
      if (!in_array($exportable, $object_names)) {
        unset($exportables[$exportable]);
      }
     }
   }

   //Disable objects
   $objects = ctools_export_crud_load_multiple($table_name, array_keys($exportables));
   foreach ($objects as $object) {
    _drush_ctools_export_disable($table_name, $object);
   }
}

/**
 * Drush callback: Export disable.
 *
 * @param $table_name
 *  Each argument will be taken as a CTools exportable table name.
 * @param $exportables
 *
 */
function drush_ctools_export_enable() {
   $args = func_get_args();
   // Table name should always be first arg...
   $table_name = array_shift($args);
   // Any additional args are assumed to be exportable names.
   $object_names = $args;

   if (!$table_name) {
    drush_log('Exportable table name empty.', 'error');
    return;
   }

   $info = _drush_ctools_export_info(array($table_name));
   $exportables = $info[$table_name];

   if (!$exportables) {
    drush_log('Exportable table name not found.', 'error');
    return;
   }

   if (empty($args)) {
    $all = drush_confirm(dt('No object names entered. Would you like to try and enable all exportables of type !type', array('!type' => $table_name)));
    if (!$all) {
      drush_log('Command cancelled', 'success');
      return;
    }
   }
   else {
     foreach (array_keys($exportables) as $exportable) {
      if (!in_array($exportable, $object_names)) {
        unset($exportables[$exportable]);
      }
     }
   }

   //Disable objects
   $objects = ctools_export_crud_load_multiple($table_name, array_keys($exportables));
   foreach ($objects as $object) {
    _drush_ctools_export_enable($table_name, $object);
   }
}

/**
 * Return array of CTools exportable info
 * based on available tables returned from
 * ctools_export_get_schemas().
 *
 * @param $table_names
 *  Array of table names to return.
 * @param $load
 *  (bool) should ctools exportable objects be loaded for each type.
 *
 * @return
 *  Nested arrays of available exportables, keyed by table name.
 */
function _drush_ctools_export_info($table_names = array(), $load = FALSE) {
  ctools_include('export');
  // Get available schemas that declare exports.
  $schemas = ctools_export_get_schemas();
  $exportables = array();

  if (!empty($schemas)) {
    // Remove types we don't want, if any.
    if (!empty($table_names)) {
      foreach (array_keys($schemas) as $table_name) {
        if (!in_array($table_name, $table_names)) {
          unset($schemas[$table_name]);
        }
      }
    }
    // Load array of available exportables for each schema.
    foreach ($schemas as $table_name => $schema) {
      // Load all objects.
      if ($load) {
        $exportables[$table_name] = ctools_export_crud_load_all($table_name);
      }
      // Get a list of exportable names.
      else {
        $exportables[$table_name] = ctools_export_default_list($table_name, $schema);
      }
    }
  }

  return $exportables;
}

/*
 * Revert a single object.
 *
 * @param $table_name
 * @param $object
 */
function _drush_ctools_export_revert($table_name, $object) {
  // type of 3 is overridden in db.
  if($object->export_type == 3) {
    // Remove from db.
    ctools_export_crud_delete($table_name, $object);
    drush_log("Reverted object: $object->name", 'success');
  }
  else {
    drush_log("Nothing to revert for: $object->name", 'success');
  }
}

/*
 * Disable a single object.
 *
 * @param $table_name
 * @param $object
 */
function _drush_ctools_export_disable($table_name, $object) {
  // type of 3 is overridden in db.
  if($object->disabled == FALSE) {
    // Disbale object.
    ctools_export_crud_disable($table_name, $object);
    drush_log("Disabled object: $object->name", 'success');
  }
  else {
    drush_log("$object->name is already disabled", 'success');
  }
}

/*
 * Enabled a single object.
 *
 * @param $table_name
 * @param $object
 */
function _drush_ctools_export_enable($table_name, $object) {
  // type of 3 is overridden in db.
  if($object->disabled == TRUE) {
    // Disbale object.
    ctools_export_crud_enable($table_name, $object);
    drush_log("Enabled object: $object->name", 'success');
  }
  else {
    drush_log("$object->name is already Enabled", 'success');
  }
}
