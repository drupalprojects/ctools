<?php

/**
 * @file
 * Definition of Drupal\ctools\Context.
 */

namespace Drupal\ctools;

/**
 * The context object is largely a wrapper around some other object, with
 * an interface to finding out what is contained and getting to both
 * the object and information about the object.
 *
 * Each context object has its own information, but some things are very
 * common, such as titles, data, keywords, etc. In particulare, the 'type'
 * of the context is important.
 */
class Context {
  var $type = NULL;
  var $data = NULL;
  // The title of this object.
  var $title = '';
  // The title of the page if this object exists
  var $page_title = '';
  // The identifier (in the UI) of this object
  var $identifier = '';
  var $argument = NULL;
  var $keyword = '';
  var $original_argument = NULL;
  var $restrictions = array();
  var $empty = FALSE;

  function ctools_context($type = 'none', $data = NULL) {
    $this->type  = $type;
    $this->data  = $data;
    $this->title = t('Unknown context');
  }

  function is_type($type) {
    if ($type == 'any' || $this->type == 'any') {
      return TRUE;
    }

    $a = is_array($type) ? $type : array($type);
    $b = is_array($this->type) ? $this->type : array($this->type);
    return (bool) array_intersect($a, $b);
  }

  function get_argument() {
    return $this->argument;
  }

  function get_original_argument() {
    if (!is_null($this->original_argument)) {
      return $this->original_argument;
    }
    return $this->argument;
  }

  function get_keyword() {
    return $this->keyword;
  }

  function get_identifier() {
    return $this->identifier;
  }

  function get_title() {
    return $this->title;
  }

  function get_page_title() {
    return $this->page_title;
  }
}
