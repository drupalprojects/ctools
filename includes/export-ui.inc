<?php

/**
 * @file
 * Provide a tool for creating UIs for exportable objects.
 *
 * See Advanced Help for documentation.
 */

use Drupal\ctools\Plugin\Type\ExportUIPluginManager;

/**
 * Process an export-ui plugin to provide it with defaults.
 */
function ctools_export_ui_process(&$plugin, $info) {
  ctools_include('export');

  $plugin += array(
    'has_menu' => TRUE,
    'title' => $plugin['name'],
    'export' => array(),
    'allowed_operations' => array(),
    'menu' => array(),
    'redirect' => array(),
    'form' => array(),
    'strings' => array(),
    'list' => NULL,
    'access' => 'administer site configuration',
  );

  // Provide CRUD access defaults based on the base 'access' setting:
  $plugin += array(
    'create_access' => $plugin['access'],
    'delete_access' => $plugin['access'],
  );

  if (empty($plugin['has_menu'])) {
    return;
  }

  // The following keys are required and the plugin cannot be processed
  // without them.
  $keys = array(
    'title_singular',
    'title_plural',
    'title_singular_proper',
    'title_plural_proper',
    'schema',
  );

  foreach ($keys as $key) {
    if (empty($plugin[$key])) {
      drupal_set_message(t('The plugin definition of @plugin is missing the %key key.', array('%key' => $key, '@plugin' => $plugin['id'])), 'error');
    }
  }

  // If we're on the modules page and building a menu, there is a design flaw
  // in Drupal core that causes modules to be installed but the schema does
  // not become available until AFTER menu rebuild. This helps smooth that
  // out. This is a HACK but it should work:
  $schema = ctools_export_get_schema($plugin['schema']);

  if (empty($schema)) {
    // If we're updating the schema may not have been read yet, so don't report this error in that case.
    if (!defined('MAINTENANCE_MODE')) {
      drupal_set_message(t('The plugin definition of @plugin cannot locate schema %schema.', array('%schema' => $plugin['schema'], '@plugin' => $plugin['id'])), 'error');
    }
    return;
  }

  if (empty($schema['export'])) {
    drupal_set_message(t('The plugin definition of @plugin uses %schema, but it has no export section.', array('%schema' => $plugin['schema'], '@plugin' => $plugin['name'])), 'error');
    return;
  }
  $plugin['export'] += $schema['export'];

  $plugin['export'] += array(
    // Add the identifier key from the schema so we don't have to call
    // ctools_export_get_schema() just for that.
    'key' => $schema['export']['key'],
  );

  // Add some default fields that appear often in exports
  // If these use different keys they can easily be specified in the
  // $plugin.

  if (empty($plugin['export']['admin_title']) && !empty($schema['fields']['admin_title'])) {
    $plugin['export']['admin_title'] = 'admin_title';
  }
  if (empty($plugin['export']['admin_description']) && !empty($schema['fields']['admin_description'])) {
    $plugin['export']['admin_description'] = 'admin_description';
  }

  // Define allowed operations, and the name of the operations.
  $plugin['allowed_operations'] += array(
    'edit'    => array('title' => t('Edit')),
    'enable'  => array('title' => t('Enable'), 'ajax' => TRUE, 'token' => TRUE),
    'disable' => array('title' => t('Disable'), 'ajax' => TRUE, 'token' => TRUE),
    'revert'  => array('title' => t('Revert')),
    'delete'  => array('title' => t('Delete')),
    'clone'   => array('title' => t('Clone')),
    'import'  => array('title' => t('Import')),
    'export'  => array('title' => t('Export')),
  );

  $plugin['menu'] += array(
    'menu_item' => str_replace(' ', '-', $plugin['name']),
    'menu_prefix' => 'admin/structure',
    'menu_title' => $plugin['title'],
    'menu_description' => '',
  );
  $base_path = ctools_export_ui_plugin_base_path($plugin);
  $prefix_count = count(explode('/', $plugin['menu']['menu_prefix']));

  $plugin['menu'] += array(
    // Default menu items that should be declared.
    'items' => array(),
  );

  $plugin['menu']['items'] += array(
    'list callback' => array(),
    'list' => array(),
    'add' => array(),
    'edit_callback' => array(),
    'edit' => array(),
  );

  $plugin['menu']['items']['list callback'] += array(
    'path' => '',
    // Menu items are translated by the menu system.
    // TODO: We need more flexibility in title. The title of the admin page
    // is not necessarily the title of the object, plus we need
    // plural, singular, proper, not proper, etc.
    'title' => $plugin['menu']['menu_title'],
    'description' => $plugin['menu']['menu_description'],
    'page callback' => 'ctools_export_ui_switcher_page',
    'page arguments' => array($plugin['name'], 'list'),
    'access callback' => 'ctools_export_ui_task_access',
    'access arguments' => array($plugin['name'], 'list'),
    'type' => MENU_NORMAL_ITEM,
  );

  $plugin['menu']['items']['list'] += array(
    'path' => 'list',
    'title' => 'List',
    'page callback' => 'ctools_export_ui_switcher_page',
    'page arguments' => array($plugin['name'], 'list'),
    'access callback' => 'ctools_export_ui_task_access',
    'access arguments' => array($plugin['name'], 'list'),
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => -10,
  );

  $plugin['menu']['items']['add'] += array(
    'path' => 'add',
    'title' => 'Add',
    'page callback' => 'ctools_export_ui_switcher_page',
    'page arguments' => array($plugin['name'], 'add'),
    'access callback' => 'ctools_export_ui_task_access',
    'access arguments' => array($plugin['name'], 'add'),
    'type' => MENU_LOCAL_ACTION,
  );

  $plugin['menu']['items']['edit_callback'] += array(
    'path' => 'list/%ctools_export_ui',
    'page callback' => 'ctools_export_ui_switcher_page',
    'page arguments' => array($plugin['name'], 'edit', $prefix_count + 2),
    'load arguments' => array($plugin['name']),
    'access callback' => 'ctools_export_ui_task_access',
    'access arguments' => array($plugin['name'], 'edit', $prefix_count + 2),
    'type' => MENU_CALLBACK,
  );

  $plugin['menu']['items']['edit'] += array(
    'path' => 'list/%ctools_export_ui/edit',
    'title' => 'Edit',
    'page callback' => 'ctools_export_ui_switcher_page',
    'page arguments' => array($plugin['name'], 'edit', $prefix_count + 2),
    'load arguments' => array($plugin['name']),
    'access callback' => 'ctools_export_ui_task_access',
    'access arguments' => array($plugin['name'], 'edit', $prefix_count + 2),
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => -10,
  );

  if ($plugin['allowed_operations']['import']) {
    $plugin['menu']['items'] += array('import' => array());
    $plugin['menu']['items']['import'] += array(
      'path' => 'import',
      'title' => 'Import',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'import'),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'import'),
      'type' => MENU_LOCAL_ACTION,
    );
  }

  if ($plugin['allowed_operations']['export']) {
    $plugin['menu']['items'] += array('export' => array());
    $plugin['menu']['items']['export'] += array(
      'path' => 'list/%ctools_export_ui/export',
      'title' => 'Export',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'export', $prefix_count + 2),
      'load arguments' => array($plugin['name']),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'export', $prefix_count + 2),
      'type' => MENU_LOCAL_TASK,
    );
  }

  if ($plugin['allowed_operations']['revert']) {
    $plugin['menu']['items'] += array('revert' => array());
    $plugin['menu']['items']['revert'] += array(
      'path' => 'list/%ctools_export_ui/revert',
      'title' => 'Revert',
      'page callback' => 'ctools_export_ui_switcher_page',
      // Note: Yes, 'delete' op is correct.
      'page arguments' => array($plugin['name'], 'delete', $prefix_count + 2),
      'load arguments' => array($plugin['name']),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'revert', $prefix_count + 2),
      'type' => MENU_CALLBACK,
    );
  }

  if ($plugin['allowed_operations']['delete']) {
    $plugin['menu']['items'] += array('delete' => array());
    $plugin['menu']['items']['delete'] += array(
      'path' => 'list/%ctools_export_ui/delete',
      'title' => 'Delete',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'delete', $prefix_count + 2),
      'load arguments' => array($plugin['name']),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'delete', $prefix_count + 2),
      'type' => MENU_CALLBACK,
    );
  }

  if ($plugin['allowed_operations']['clone']) {
    $plugin['menu']['items'] += array('clone' => array());
    $plugin['menu']['items']['clone'] += array(
      'path' => 'list/%ctools_export_ui/clone',
      'title' => 'Clone',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'clone', $prefix_count + 2),
      'load arguments' => array($plugin['name']),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'clone', $prefix_count + 2),
      'type' => MENU_CALLBACK,
    );
  }

  if ($plugin['allowed_operations']['enable']) {
    $plugin['menu']['items'] += array('enable' => array());
    $plugin['menu']['items']['enable'] += array(
      'path' => 'list/%ctools_export_ui/enable',
      'title' => 'Enable',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'enable', $prefix_count + 2),
      'load arguments' => array($plugin['name']),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'enable', $prefix_count + 2),
      'type' => MENU_CALLBACK,
    );
  }

  if ($plugin['allowed_operations']['disable']) {
    $plugin['menu']['items'] += array('disable' => array());
    $plugin['menu']['items']['disable'] += array(
      'path' => 'list/%ctools_export_ui/disable',
      'title' => 'Disable',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'disable', $prefix_count + 2),
      'load arguments' => array($plugin['name']),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'disable', $prefix_count + 2),
      'type' => MENU_CALLBACK,
    );
  }

  // Define some redirects that should happen after edit/add/clone/delete operations.
  $plugin['redirect'] += array(
    'add' => $base_path,
    'clone' => $base_path,
    'edit' => $base_path,
    'delete' => $base_path,
    'revert' => $base_path,
    'import' => $base_path,
  );

  // Define form elements.
  $plugin['form'] += array(
    'settings' => function_exists($plugin['name'] . '_form') ? $plugin['name'] . '_form' : '',
    'validate' => function_exists($plugin['name'] . '_form_validate') ? $plugin['name'] . '_form_validate' : '',
    'submit' => function_exists($plugin['name'] . '_form_submit') ? $plugin['name'] . '_form_submit' : '',
  );

  // Define strings.

  // For all strings, %title may be filled in at a later time via str_replace
  // since we do not know the title now.
  $plugin['strings'] += array(
    'title' => array(),
    'confirmation' => array(),
    'help' => array(),
    'message' => array(),
  );

  // Strings used in drupal_set_title().
  $plugin['strings']['title'] += array(
    'add' => t('Add a new @plugin', array('@plugin' => $plugin['title_singular'])),
    // The "%title" will be replaced in ctools_export_ui_form(), as in this
    // stage we dont have the specific exportable object.
    'edit' => t('Edit @plugin %title', array('@plugin' => $plugin['title_singular'])),
    'clone' => t('Clone @plugin %title', array('@plugin' => $plugin['title_singular'])),

    'import' => t('Import @plugin', array('@plugin' => $plugin['title_singular'])),
    'export' => t('Export @plugin %title', array('@plugin' => $plugin['title_singular'])),
  );

  // Strings used in confirmation pages.
  $plugin['strings']['confirmation'] += array(
    'revert' => array(),
    'delete' => array(),
    'add' => array(),
    'edit' => array(),
  );

  $plugin['strings']['confirmation']['revert'] += array(
    'question' => t('Are you sure you want to revert %title?'),
    'information' => t('This action will permanently remove any customizations made to this item.'),
    'success' => t('The item has been reverted.'),
  );

  $plugin['strings']['confirmation']['delete'] += array(
    'question' => t('Are you sure you want to delete %title?'),
    'information' => t('This action will permanently remove this item from your database..'),
    'success' => t('The item has been deleted.'),
  );

  $plugin['strings']['confirmation']['add'] += array(
    'success' => t('%title has been created.'),
    'fail' => t('%title could not be created.'),
  );

  $plugin['strings']['confirmation']['edit'] += array(
    'success' => t('%title has been updated.'),
    'fail' => t('%title could not be updated.'),
  );

  // Strings used in $forms.
  $plugin['strings']['help'] += array(
    'import' => t('You can import an exported definition by pasting the exported object code into the field below.'),
  );

  // Strings used in drupal_set_message().
  $plugin['strings']['message'] += array(
    'enable' => t('@plugin %title was enabled.', array('@plugin' => $plugin['title_singular_proper'])),
    'disable' => t('@plugin %title was disabled.', array('@plugin' => $plugin['title_singular_proper'])),
    'no items' => t('There are no @titles to display.', array('@titles' => $plugin['title_plural'])),
  );
}

/**
 * Get the class to handle creating a list of exportable items.
 *
 * If a plugin does not define a lister class at all, then the default
 * lister class will be used.
 *
 * @deprecated use ctools_export_ui_get_instance instead
 *
 * @return
 *   Either the lister class or FALSE if one could not be had.
 */
function ctools_export_ui_get_handler($plugin) {
  $cache = &drupal_static(__FUNCTION__, array());
  if (empty($cache[$plugin['name']])) {
    // If a list class is not specified by the plugin, fall back to the
    // default ctools_export_ui plugin instead.
    if (empty($plugin['handler'])) {
      $default = ctools_get_export_ui('ctools_export_ui');
      $class = ctools_plugin_get_class($default, 'handler');
    }
    else {
      $class = ctools_plugin_get_class($plugin, 'handler');
    }

    if ($class) {
      $cache[$plugin['name']] = new $class();
      $cache[$plugin['name']]->init($plugin);
    }
  }
  return !empty($cache[$plugin['name']]) ? $cache[$plugin['name']] : FALSE;
}

/**
 * Get an instance of an export ui plugin.
 *
 * @param string $id
 *   The id of the export ui plugin.
 *
 * @return Drupal\ctools\Plugin\ctools\export_ui\ExportUIPluginBase
 *   The instance of the export ui plugin.
 */
function ctools_export_ui_get_instance($id = 'standard') {
  $manager = ctools_export_ui_get_manager();
  $definition = $manager->getDefinition($id);
  if (!empty($definition)) {
    $instance = $manager->createInstance($id);
    $instance->init($definition);
    return $instance;
  }
  else {
    debug(format_string("instance @id not found", array('@id' => $id)));
  }
}

function ctools_export_ui_get_manager() {
  static $manager = NULL;
  if (!isset($manager)) {
    $manager = new ExportUIPluginManager();
  }
  return $manager;
}

/**
 * Get the base path from a plugin.
 *
 * @param $plugin
 *   The plugin.
 *
 * @return
 *   The menu path to the plugin's list.
 */
function ctools_export_ui_plugin_base_path($plugin) {
  return $plugin['menu']['menu_prefix'] . '/' . $plugin['menu']['menu_item'];
}

/**
 * Get the path to a specific menu item from a plugin.
 *
 * @param $plugin
 *   The plugin name.
 * @param $item_id
 *   The id in the menu items from the plugin.
 * @param $export_key
 *   The export key of the item being edited, if it exists.
 * @return
 *   The menu path to the plugin's list.
 */
function ctools_export_ui_plugin_menu_path($plugin, $item_id, $export_key = NULL) {
  $path = $plugin['menu']['items'][$item_id]['path'];
  if ($export_key) {
    $path = str_replace('%ctools_export_ui', $export_key, $path);
  }
  return ctools_export_ui_plugin_base_path($plugin) . '/' . $path;
}

/**
 * Helper function to include CTools plugins and get an export-ui exportable.
 *
 * @param $plugin_name
 *   The plugin that should be laoded.
 */
function ctools_get_export_ui($plugin_name) {
  ctools_include('plugins');
  $manager = ctools_export_ui_get_manager();
  $definition = $manager->getDefinition($plugin_name);
  return $definition;

}

/**
 * Helper function to include CTools plugins and get all export-ui exportables.
 */
function ctools_get_export_uis() {
  $manager = ctools_export_ui_get_manager();
  $definitions = $manager->getDefinitions();
  return $definitions;
}

/**
 * Main page callback to manipulate exportables.
 *
 * This simply loads the object defined in the plugin and hands it off to
 * a method based upon the name of the operation in use. This can easily
 * be used to add more ops.
 */
function ctools_export_ui_switcher_page($plugin_name, $op) {
  $args = func_get_args();
  $js = !empty($_REQUEST['js']);

  // Load the $plugin information
  $handler = ctools_export_ui_get_instance($plugin_name);

  if ($handler) {
    $method = $op . '_page';
    if (method_exists($handler, $method)) {
      // replace the first two arguments:
      $args[0] = $js;
      $args[1] = $_POST;
      return call_user_func_array(array($handler, $method), $args);
    }
  }
  else {
    return t('Configuration error. No handler found.');
  }
}

/**
 * @todo Move all the form callback/helper functions into an extra .form.inc
 *  file.
 */

// Since Drupal's forms are completely procedural, these forms will
// mostly just be pass-throughs back to the object.

/**
 * Add all appropriate includes to forms so that caching the form
 * still loads the files that we need.
 */
function _ctools_export_ui_add_form_files($form, &$form_state) {
  ctools_form_include($form_state, 'export');
  ctools_form_include($form_state, 'export-ui');

  // Also make sure the plugin .inc file is loaded.
  ctools_form_include_file($form_state, $form_state['object']->plugin['path'] . '/' . $form_state['object']->plugin['file']);
}

/**
 * Form callback to handle the filter/sort form when listing items.
 *
 * This simply loads the object defined in the plugin and hands it off.
 */
function ctools_export_ui_list_form($form, $form_state) {
  $form_state['object']->list_form($form, $form_state);
  return $form;
}

/**
 * Validate handler for ctools_export_ui_list_form.
 */
function ctools_export_ui_list_form_validate(&$form, &$form_state) {
  $form_state['object']->list_form_validate($form, $form_state);
}

/**
 * Submit handler for ctools_export_ui_list_form.
 */
function ctools_export_ui_list_form_submit(&$form, &$form_state) {
  $form_state['object']->list_form_submit($form, $form_state);
}

/**
 * Form callback to edit an exportable item.
 *
 * This simply loads the object defined in the plugin and hands it off.
 */
function ctools_export_ui_edit_item_form($form, &$form_state) {
  // When called using #ajax via ajax_form_callback(), 'export' may
  // not be included so include it here.
  _ctools_export_ui_add_form_files($form, $form_state);

  $form_state['object']->edit_form($form, $form_state);
  return $form;
}

/**
 * Validate handler for ctools_export_ui_edit_item_form.
 */
function ctools_export_ui_edit_item_form_validate(&$form, &$form_state) {
  $form_state['object']->edit_form_validate($form, $form_state);
}

/**
 * Submit handler for ctools_export_ui_edit_item_form.
 */
function ctools_export_ui_edit_item_form_submit(&$form, &$form_state) {
  $form_state['object']->edit_form_submit($form, $form_state);
}

/**
 * Submit handler to delete for ctools_export_ui_edit_item_form
 *
 * @todo Put this on a callback in the object.
 */
function ctools_export_ui_edit_item_form_delete(&$form, &$form_state) {
  _ctools_export_ui_add_form_files($form, $form_state);

  $export_key = $form_state['plugin']['export']['key'];
  $path = $form_state['item']->export_type & EXPORT_IN_CODE ? 'revert' : 'delete';

  drupal_goto(ctools_export_ui_plugin_menu_path($form_state['plugin'], $path, $form_state['item']->{$export_key}), array('cancel_path' => current_path()));
}

/**
 * Validate that an export item name is acceptable and unique during add.
 */
function ctools_export_ui_edit_name_validate($element, &$form_state) {
  $plugin = $form_state['plugin'];
  // Check for string identifier sanity
  if (!preg_match('!^[a-z0-9_]+$!', $element['#value'])) {
    form_error($element, t('The export id can only consist of lowercase letters, underscores, and numbers.'));
    return;
  }

  // Check for name collision
  if (empty($form_state['item']->export_ui_allow_overwrite) && $exists = ctools_export_crud_load($plugin['schema'], $element['#value'])) {
    form_error($element, t('A @plugin with this name already exists. Please choose another name or delete the existing item before creating a new one.', array('@plugin' => $plugin['title_singular'])));
  }
}

/**
 * Test for #machine_name type to see if an export exists.
 */
function ctools_export_ui_edit_name_exists($name, $element, &$form_state) {
  $plugin = $form_state['plugin'];

  return (empty($form_state['item']->export_ui_allow_overwrite) && ctools_export_crud_load($plugin['schema'], $name));
}

/**
 * Delete/Revert confirm form.
 *
 * @todo -- call back into the object instead.
 */
function ctools_export_ui_delete_confirm_form($form, &$form_state) {
  _ctools_export_ui_add_form_files($form, $form_state);

  $plugin = $form_state['plugin'];
  $item = $form_state['item'];

  $form = array();

  $export_key = $plugin['export']['key'];
  $question = str_replace('%title', check_plain($item->{$export_key}), $plugin['strings']['confirmation'][$form_state['op']]['question']);
  $path = empty($_REQUEST['cancel_path']) ? ctools_export_ui_plugin_base_path($plugin) : $_REQUEST['cancel_path'];

  $form = confirm_form($form,
    $question,
    $path,
    $plugin['strings']['confirmation'][$form_state['op']]['information'],
    $plugin['allowed_operations'][$form_state['op']]['title'], t('Cancel')
  );
  return $form;
}

// --------------------------------------------------------------------------
// Forms and callbacks for using the edit system with the wizard.

/**
 * Form callback to edit an exportable item using the wizard
 *
 * This simply loads the object defined in the plugin and hands it off.
 */
function ctools_export_ui_edit_item_wizard_form($form, &$form_state) {
  _ctools_export_ui_add_form_files($form, $form_state);

  $method = 'edit_form_' . $form_state['step'];
  if (!method_exists($form_state['object'], $method)) {
    $method = 'edit_form';
  }

  $form_state['object']->$method($form, $form_state);
  return $form;
}

/**
 * Validate handler for ctools_export_ui_edit_item_wizard_form.
 */
function ctools_export_ui_edit_item_wizard_form_validate(&$form, &$form_state) {
  $method = 'edit_form_' . $form_state['step'] . '_validate';
  if (!method_exists($form_state['object'], $method)) {
    $method = 'edit_form_validate';
  }

  $form_state['object']->$method($form, $form_state);

  // Additionally, if there were no errors from that, and we're finishing,
  // perform a final validate to make sure everything is ok.
  if (isset($form_state['clicked_button']['#wizard type']) && $form_state['clicked_button']['#wizard type'] == 'finish' && !form_get_errors()) {
    $form_state['object']->edit_finish_validate($form, $form_state);
  }
}

/**
 * Submit handler for ctools_export_ui_edit_item_wizard_form.
 */
function ctools_export_ui_edit_item_wizard_form_submit(&$form, &$form_state) {
  $method = 'edit_form_' . $form_state['step'] . '_submit';
  if (!method_exists($form_state['object'], $method)) {
    $method = 'edit_form_submit';
  }

  $form_state['object']->$method($form, $form_state);
}

/**
 * Wizard 'back' callback when using a wizard to edit an item.
 */
function ctools_export_ui_wizard_back(&$form_state) {
  $form_state['object']->edit_wizard_back($form_state);
}

/**
 * Wizard 'next' callback when using a wizard to edit an item.
 */
function ctools_export_ui_wizard_next(&$form_state) {
  $form_state['object']->edit_wizard_next($form_state);
}

/**
 * Wizard 'cancel' callback when using a wizard to edit an item.
 */
function ctools_export_ui_wizard_cancel(&$form_state) {
  $form_state['object']->edit_wizard_cancel($form_state);
}

/**
 * Wizard 'finish' callback when using a wizard to edit an item.
 */
function ctools_export_ui_wizard_finish(&$form_state) {
  $form_state['object']->edit_wizard_finish($form_state);
}
