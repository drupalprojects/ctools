<?php

/**
 * @file
 * Definition of Drupal\views_content\Plugin\views\style\CToolsContext.
 */

namespace Drupal\views_content\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Default style plugin to render rows one after another with no
 * decorations.
 *
 * @ingroup views_style_plugins
 *
 *  * @Plugin(
 *   id = "ctools_context",
 *   title = @Translation("Context"),
 *   admin = @Translation("Context"),
 *   help = @Translation("Contains rows in contexts."),
 *   theme = "views_view_unformatted",
 *   help_topic = "style-context",
 *   register_theme = FALSE,
 *   type = "context",
 *   returns_context = TRUE
 * )
 */
class CToolsContext extends StylePluginBase {
  var $rows = array();

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * This option only makes sense on style plugins without row plugins, like
   * for example table.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Render the display in this style.
   */
  public function render() {
    // @todo The property previewing seems to be wrong.
    if (!empty($this->view->display_handler->previewing)) {
      return parent::render();
    }

    $this->rows = array();
    $this->groups = array();

    // Some engines like solr key results on ids, but rendering really expects
    // things to be keyed exclusively by row index. Using array_values()
    // guarantees that.
    $this->view->result = array_values($this->view->result);

    // Group the rows according to the grouping field, if specified.
    $sets = $this->render_grouping($this->view->result, $this->options['grouping']);

    // Render each group separately and concatenate.  Plugins may override this
    // method if they wish some other way of handling grouping.
    $output = '';
    foreach ($sets as $title => $records) {
      foreach ($records as $row_index => $row) {
        $this->view->row_index = $row_index;
        $this->rows[$row_index] = $this->row_plugin->render($row);
        $this->groups[$row_index] = $title;
      }
    }
    unset($this->view->row_index);
    return $this->rows;
  }

}
