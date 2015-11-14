<?php
/**
 * @file
 * Contains \Drupal\ctools_views\Plugin\Display\Block.
 */

namespace Drupal\ctools_views\Plugin\Display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Plugin\views\display\Block as CoreBlock;

class Block extends CoreBlock {

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    $filtered_allow = array_filter($this->getOption('allow'));
    $filter_options = [
      'items_per_page' => $this->t('Items per page'),
      'hide_fields' => $this->t('Hide fields'),
      'configure_filters' => $this->t('Configure filters')
    ];
    $filter_intersect = array_intersect_key($filter_options, $filtered_allow);

    $options['allow'] = array(
      'category' => 'block',
      'title' => $this->t('Allow settings'),
      'value' => empty($filtered_allow) ? $this->t('None') : implode(', ', $filter_intersect),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = $form['allow']['#options'];
    $options['hide_fields'] = $this->t('Hide fields');
    $options['configure_filters'] = $this->t('Configure filters');
    $form['allow']['#options'] = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);
    $allow_settings = array_filter($this->getOption('allow'));

    $block_configuration = $block->getConfiguration();
    foreach ($allow_settings as $type => $enabled) {
      if (empty($enabled)) {
        continue;
      }
      switch ($type) {
        case 'hide_fields':
          $field_options = [];
          $fields = $this->getOption('fields');
          $header = [
            'hide' => $this->t('Hide'),
            'label' => $this->t('Label'),
            'weight' => $this->t('Weight')
          ];
          $form['override']['order_fields'] = [
            '#type' => 'table',
            '#header' => $header,
            '#rows' => array(),
            '#tabledrag' => [
              [
                'action' => 'order',
                'relationship' => 'sibling',
                'group' => 'field-weight',
              ]
            ],
            '#attributes' => ['id' => 'order-fields'],
          ];
          foreach ($fields as $field_name => $field_info) {
            $field_options[$field_name] = $field_info['label'];
            $form['override']['order_fields'][$field_name]['#attributes']['class'][] = 'draggable';
            $form['override']['order_fields'][$field_name]['#weight'] = !empty($block_configuration['fields'][$field_name]['weight']) ? $block_configuration['fields'][$field_name]['weight'] : '';
            $form['override']['order_fields'][$field_name]['hide'] = [
              '#type' => 'checkbox',
              '#default_value' => !empty($block_configuration['fields'][$field_name]['hide']) ? $block_configuration['fields'][$field_name]['hide'] : 0,
            ];
            $form['override']['order_fields'][$field_name]['label'] = [
              '#markup' => $field_info['label'],
            ];
            $form['override']['order_fields'][$field_name]['weight'] = [
              '#type' => 'weight',
              '#title' => $this->t('Weight for @title', ['@title' => $field_info['label']]),
              '#title_display' => 'invisible',
              '#delta' => 50,
              '#default_value' => !empty($block_configuration['fields'][$field_name]['weight']) ? $block_configuration['fields'][$field_name]['weight'] : 0,
              '#attributes' => ['class' => ['field-weight']],
            ];
          }
          break;
        case 'configure_filters':
          $filters = $this->getOption('filters');
          $manager = \Drupal::service('plugin.manager.views.filter');
          foreach ($filters as $filter_name => $values) {
            if (!empty($values['exposed']) && $values['exposed']) {
              $form['override']['filters'][$filter_name] = [
                '#type' => 'details',
                '#title' => $values['expose']['label'],
              ];
              $plugin_form = [];
              /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $plugin */
              $plugin = $manager->getHandler($values);
              $plugin->init($this->view, $this, $values);
              $plugin->buildExposedForm($plugin_form, $form_state);
              $form['override']['filters'][$filter_name]['form'] = $plugin_form;
              if (!empty($block_configuration["filter"][$filter_name]['value'])) {
                $form['override']['filters'][$filter_name]['form'][$plugin->exposedInfo()['value']]['#default_value'] = $block_configuration["filter"][$filter_name]['value'];
              }
              $form['override']['filters'][$filter_name]['plugin'] = [
                '#type' => 'value',
                '#value' => $plugin,
              ];
              $form['override']['filters'][$filter_name]['disable'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Disable'),
                '#default_value' => $block_configuration['filter'][$filter_name]['disable'],
              ];
            }
          }
          break;
      }
    }
    return $form;
  }

  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    parent::blockSubmit($block, $form, $form_state);
    $configuration = $block->getConfiguration();
    unset($configuration['hide_fields']);
    if ($fields = array_filter($form_state->getValue(['override', 'order_fields']))) {
      $configuration['fields'] = $fields;
    }
    $form_state->unsetValue(['override', 'hide_fields']);

    // Handle exposed filters as configuration options.
    if ($filters = $form_state->getValue(['override', 'filters'])) {
      foreach ($filters as $filter_name => $filter) {
        /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $plugin */
        $plugin = $form_state->getValue(['override', 'filters', $filter_name, 'plugin']);
        $form_value = $form_state->getValue(['override', 'filters', $filter_name, 'form', $plugin->exposedInfo()['value']]);
        $disable = $form_state->getValue(['override', 'filters', $filter_name, 'disable']);
        // if this is disabled, we don't really care about other stuff.
        if ($disable) {
          $configuration["filter"][$filter_name]['disable'] = $disable;
          continue;
        }
        elseif (!empty($configuration["filter"][$filter_name]['disable'])) {
          unset($configuration["filter"][$filter_name]['disable']);
        }
        // Handle grouped filters first.
        if ($plugin->isAGroup()) {
          $value = $plugin->convertExposedInput($filters, $form_value);
          // Null values on a grouped filter are no-input. We can ignore them.
          if (is_null($value)) {
            // Don't save unnecessary configuration.
            if (isset($configuration["filter"][$filter_name]['value'])) {
              unset($configuration["filter"][$filter_name]['value']);
            }
            continue;
          }
          // If it's not a null value, save input and continue to the next iteration.

          $configuration["filter"][$filter_name]['value'] = $form_value;
          continue;
        }

        // If we have value options & our value is in the array, save it.
        if (method_exists($plugin, 'getValueOptions') && array_key_exists($form_value, $plugin->getValueOptions())) {
          $configuration["filter"][$filter_name]['value'] = $form_value;
          continue;
        }
        // If the value is not the same as what's set in the view, store it.
        elseif ($form_value != $plugin->value && !method_exists($plugin, 'getValueOptions')) {
          $configuration["filter"][$filter_name]['value'] = $form_value;
          continue;
        }
        // If we made it this far, we're not saving, so remove the value from
        // configuration if it exists.
        if (isset($configuration["filter"][$filter_name]['value'])) {
          unset($configuration["filter"][$filter_name]['value']);
        }
      }
    }

    $block->setConfiguration($configuration);
  }

  public function preBlockBuild(ViewsBlock $block) {
    parent::preBlockBuild($block);
    list(, $display_id) = explode('-', $block->getDerivativeId());
    $config = $block->getConfiguration();
    if (!empty($config['fields']) && $this->view->getStyle()->usesFields()) {
      $fields = $this->view->getHandlers('field');
      foreach ($config['fields'] as $field_name => $values) {
        $this->view->removeHandler($display_id, 'field', $field_name);
        if (!$values['hide']) {
          $this->view->addHandler($display_id, 'field', $fields[$field_name]['table'], $field_name, $fields[$field_name]);
        }
      }
    }
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase[] $filters */
    $filters = $this->view->getHandlers('filter', $display_id);
    foreach ($filters as $filter_name => $filter) {
      if (!empty($config["filter"][$filter_name]['value']) && empty($config["filter"][$filter_name]['disable'])) {
        $filter['value'] = $this->setValueByOperator($filter['operator'], $config["filter"][$filter_name]['value']);
        unset($filter['exposed']);
        $this->view->setHandler($display_id, 'filter', $filter_name, $filter);
      }
      elseif (!empty($config["filter"][$filter_name]['disable'])) {
        $this->view->removeHandler($display_id, 'filter', $filter_name);
      }
    }
  }

  public function usesExposed() {
    return TRUE;
  }

  /**
   * Exposed widgets typically only work with ajax in Drupal core, however
   * #2605218 totally breaks the rest of the functionality in this display and
   * in Core's Block display as well, so we allow non-ajax block views to use
   * exposed filters and manually set the #action to the current request uri.
   */
  public function elementPreRender(array $element) {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $element['#view'];
    if (!empty($view->exposed_widgets['#action']) && !$view->ajaxEnabled()) {
      $view->exposed_widgets['#action'] = \Drupal::request()->getRequestUri();
    }
    return parent::elementPreRender($element);
  }

  /**
   * @param string $operator
   * @param string $value
   *
   * @return mixed
   */
  protected function setValueByOperator($operator, $value) {
    switch ($operator) {
      case '=':
      case 'contains':
        return $value;
      case 'in':
        return [$value];
      default:
        return $value;
    }
  }

}
