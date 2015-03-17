<?php

/**
 * @file
 * Describe hooks provided by the Page Manager module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Implements hook_page_manager_page_presave
 * 
 * @parm stdClass $page
 */
function hook_page_manager_page_presave($page) {
  // Custom logic
}

/**
 * @todo.
 *
 * @param array $result
 *   @todo.
 * @param object $page
 *   @todo.
 */
function hook_page_manager_operations_alter(&$result, &$page) {
  // @todo.
}

/**
 * @todo.
 *
 * @param array $operations
 *   @todo.
 * @param object $handler
 *   @todo.
 */
function hook_page_manager_variant_operations_alter(&$operations, &$handler) {
  // @todo.
}

/**
 * @} End of "addtogroup hooks".
 */
