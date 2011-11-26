<?php
/**
 * @file
 * Theme definitions for export UI.
 */
/**
 * Delegated implementation of hook_theme()
 */
function ctools_export_ui_theme(&$items) {
  $items['ctools_operation_group'] = array(
    'render element' => 'element',
    'file' => 'includes/export-ui.theme.inc',
  );
  $items['ctools_export_ui_operations_page'] = array(
    'render element' => 'element',
    'file' => 'includes/export-ui.theme.inc',
  );
}

/**
 * Theme a CTools operation group.
 *
 * This turns all of the children into LI tags in a UL
 * as well as adding an optional title.
 */
function theme_ctools_operation_group($element) {
  // Get the children of the element, sorted by weight.
  $children = element_children($elements, TRUE);

  $title = '';
  if (!empty($element['#title'])) {
    $title = '<div class="operations-group-title">' . $element['#title'] . "</div>\n";
  }

  $content = '';
  $first = reset($children);
  $last = end($children);

  // Render each child and put it into an <li>.
  foreach ($children as $count => $key) {
    // Use '#li attributes' if it exists, or an empty array otherwise.
    $attributes = !empty($element[$key]['#li attributes']) ? $element[$key]['#li attributes'] : array();
    $attributes['class'] = 'operations-item';
    if ($key == $first) {
      $attributes['class'][] = 'operation-first';
    }
    if ($key == $last) {
      $attributes['class'][] = 'operation-last';
    }

    // Automatically calculate href for operations if the are a link with no
    // #href already set:
    if (!empty($element[$key]['#type']) && $element[$key]['#type'] == 'link' && empty($element[$key]['#href'])) {
      $element[$key]['#href'] = $element['#uri'];
      $element[$key]['#attributes']['class'][] = 'use-ajax';
    }

    // Add active/not active flag.
    $attributes['class'][] = !empty($element['#active']) ? 'operation-active' : 'operation-not-active';

    // Add changed flag
    $attributes['class'][] = !empty($element['#changed']) ? 'operation-changed' : 'operation-not-changed';

    $content .= '<li ' . drupal_attributes($attributes) . '>' . drupal_render($element[$key]) . "</li>\n";
  }

  if (!empty($element['#collapsible'])) {
    $content = theme('ctools_collapsible', array('handle' => $title, 'content' => $content, 'collapsed' => empty($element['#active'])));
  }
  else {
    $content = $title . $content;
  }

  // Generate the <ul> wrapper.
  $ul_attributes = array(
    'class' => array('operations-group'),
  );

  // Add active flag
  $ul_attributes['class'][] = !empty($element['#active']) ? 'operation-active' : 'operation-not-active';
  // Add changed flag
  $ul_attributes['class'][] = !empty($element['#changed']) ? 'operation-changed' : 'operation-not-changed';

  $output .= '<ul ' . drupal_attributes($ul_attributes) . ">\n";
  $output .= $content;
  $output .= "</ul>\n";

  return $output;
}

/**
 * Preprocess the operations page.
 *
 * We support only some minimal regions on the page. There's an 'actions' set
 * set of operations on the top, and the main operations set on the left.
 * This is probably fine for 98% of the applications that might use this UI.
 */
function template_preprocess_ctools_export_ui_operations_page(&$vars) {
  $element = $vars['element'];

  $vars['operations'] = !empty($element['#operations']['operations']) ? $element['#operations']['operations'] : '';
  $vars['actions'] = !empty($element['#operations']['actions']) ? $element['#operations']['actions'] : '';
  $vars['content'] = $element['#content'];
  $vars['save'] = drupal_render($element['#save_form']);
}

/**
 * Render the actual page.
 *
 * There are two reasons this is a theme function and not a template.
 * - It will likely not be overridden by themers, so there's no real
 *   need to make it available.
 * - Theme system requires template and preprocess files be in the same
 *   directory and that is annoying to the CTools organization system.
 */
function theme_ctools_export_ui_operations_page($vars) {
  extract($vars);

  return <<<EOF
<div id="ctools-export-ui-edit">
  <div class="ctools-export-ui-wrapper">
      <div class="primary-actions clearfix actions">
        $actions
      </div>
    <div class="ctools-export-ui-tabs clearfix">
      <div class="ctools-export-ui-edit-operations">
        <div class="inside">
          $operations
        </div>
      </div>
      <div class="ctools-export-ui-ajax-pad">
        <div class="inside">
          <div class="content-header">
            <div class="content-title">
              {$content['title']}
            </div>
          </div>

          <div class="content-content">
              <div class="description">
                {$content['description']}
              </div>
            {$content['content']}
          </div>
        </div>
      </div>
    </div>
  </div>
  $save;
</div>
EOF;

}
