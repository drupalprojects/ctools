<?php

/**
 * @file
 * Definition of Drupal\ctools\MathExpressionStack
 */

namespace Drupal\ctools;

/**
 * Represents an internal stack for the math expression class.
 *
 * @see Drupal\ctools\MathExpression
 */
class MathExpressionStack {
  var $stack = array();
  var $count = 0;

  function push($val) {
    $this->stack[$this->count] = $val;
    $this->count++;
  }

  function pop() {
    if ($this->count > 0) {
      $this->count--;
      return $this->stack[$this->count];
    }
    return null;
  }

  function last($n=1) {
    return !empty($this->stack[$this->count-$n]) ? $this->stack[$this->count-$n] : NULL;
  }
}