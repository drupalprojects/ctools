<?php

use Drupal\ctools\ContextRequired;
use Drupal\ctools\ContextOptional;

/**
 * @file
 * Definition of Drupal\ctools\ContextOptional.
 */

namespace Drupal\ctools;

/**
 * Used to compare to see if a list of contexts match an optional context. This
 * can produce empty contexts to use as placeholders.
 */
class ContextOptional extends ContextRequired {
  var $required = FALSE;
  function ContextOptional() {
    $args = func_get_args();
    call_user_func_array(array($this, 'ContextRequired'), $args);
  }

  /**
   * Add the 'empty' context which is possible for optional
   */
  function add_empty(&$contexts) {
    $context = new Context('any');
    $context->title      = t('No context');
    $context->identifier = t('No context');
    $contexts = array_merge(array('empty' => $context), $contexts);
  }

  function filter($contexts) {
    $this->add_empty($contexts);
    return parent::filter($contexts);
  }

  function select($contexts, $context) {
    $this->add_empty($contexts);
    if (empty($context)) {
      return $contexts['empty'];
    }

    $result = parent::select($contexts, $context);

    // Don't flip out if it can't find the context; this is optional, put
    // in an empty.
    if ($result == FALSE) {
      $result = $contexts['empty'];
    }
    return $result;
  }
}
