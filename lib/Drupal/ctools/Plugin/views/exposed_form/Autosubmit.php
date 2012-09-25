<?php

/**
 * @file
 * Definition of Drupal\ctools\Plugin\views\exposed_form\Autosubmit.
 */

namespace Drupal\ctools\Plugin\views\exposed_form;

use Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Extends the exposed form to provide an autosubmit functionality.
 *
 * @ingroup views_exposed_form_plugins
 *
 * @Plugin(
 *   id = "autosubmit",
 *   title = @Translation("Autosubmit"),
 *   help = @Translation("Exposed form with autosubmit")
 * )
 */
class Autosubmit extends ExposedFormPluginBase {
  /**
   * Overrides Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase::defineOptions().
   */
  protected function defineOptions() {
    return parent::defineOptions();

    $options['autosubmit_hide'] = array('default' => TRUE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['autosubmit_hide'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide submit button'),
      '#description' => t('Hide submit button if javascript is enabled.'),
      '#default_value' => $this->options['autosubmit_hide'],
    );
  }

  /**
   * Overrides Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase::exposed_form_alter().
   */
  function exposed_form_alter(&$form, &$form_state) {
    // Apply autosubmit values.
    $form = array_merge_recursive($form, array('#attributes' => array('class' => array('ctools-auto-submit-full-form'))));
    $form['submit']['#attributes']['class'][] = 'ctools-use-ajax';
    $form['submit']['#attributes']['class'][] = 'ctools-auto-submit-click';
    $form['#attached']['js'][] = drupal_get_path('module', 'ctools') . '/js/auto-submit.js';

    if (!empty($this->options['autosubmit_hide'])) {
      $form['submit']['#attributes']['class'][] = 'js-hide';
    }
  }

}
