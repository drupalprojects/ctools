<?php

/**
 * @file
 * Definition of Drupal\ctools\ContextRequired.
 */

namespace Drupal\ctools;

/**
 * Used to create a method of comparing if a list of contexts
 * match a required context type.
 */
class ContextRequired {
  var $keywords = '';

  /**
   * If set, the title will be used in the selector to identify
   * the context. This is very useful when multiple contexts
   * are required to inform the user will be used for what.
   */
  var $title = NULL;

  /**
   * Test to see if this context is required.
   */
  var $required = TRUE;

  /**
   * If TRUE, skip the check in ContextRequired::select()
   * for contexts whose names may have changed.
   */
  var $skip_name_check = FALSE;

  /**
   *
   * @param $title
   *   The first parameter should be the 'title' of the context for use
   *   in UYI selectors when multiple contexts qualify.
   * @param ...
   *   One or more keywords to use for matching which contexts are allowed.
   */
  function ContextRequired($title) {
    $args = func_get_args();
    $this->title = array_shift($args);

    // If we have a boolean value at the end for $skip_name_check, store it
    if (is_bool(end($args))) {
      $this->skip_name_check = array_pop($args);
    }

    // If we were given restrictions at the end, store them.
    if (count($args) > 1 && is_array(end($args))) {
      $this->restrictions = array_pop($args);
    }

    if (count($args) == 1) {
      $args = array_shift($args);
    }
    $this->keywords = $args;
  }

  function filter($contexts) {
    $result = array();

    // See which of these contexts are valid
    foreach ((array) $contexts as $cid => $context) {
      if ($context->is_type($this->keywords)) {
        // Compare to see if our contexts were met.
        if (!empty($this->restrictions) && !empty($context->restrictions)) {
          foreach ($this->restrictions as $key => $values) {
            // If we have a restriction, the context must either not have that
            // restriction listed, which means we simply don't know what it is,
            // or there must be an intersection of the restricted values on
            // both sides.
            if (!is_array($values)) {
              $values = array($values);
            }
            if (!empty($context->restrictions[$key]) && !array_intersect($values, $context->restrictions[$key])) {
              continue 2;
            }
          }
        }
        $result[$cid] = $context;
      }
    }

    return $result;
  }

  function select($contexts, $context) {
    if (!is_array($contexts)) {
      $contexts = array($contexts);
    }

    // If we had requested a $context but that $context doesn't exist
    // in our context list, there is a good chance that what happened
    // is our context IDs changed. See if there's another context
    // that satisfies our requirements.
    if (!$this->skip_name_check && !empty($context) && !isset($contexts[$context])) {
      $choices = $this->filter($contexts);

      // If we got a hit, take the first one that matches.
      if ($choices) {
        $keys = array_keys($choices);
        $context = reset($keys);
      }
    }

    if (empty($context) || empty($contexts[$context])) {
      return FALSE;
    }
    return $contexts[$context];
  }
}
