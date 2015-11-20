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
      'offset' => $this->t('Pager offset'),
      'pager' => $this->t('Pager type'),
      'hide_fields' => $this->t('Hide fields'),
      'sort_fields' => $this->t('Reorder fields'),
      'configure_filters' => $this->t('Configure filters'),
      'disable_filters' => $this->t('Disable filters')
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
    $options['offset'] = $this->t('Pager offset');
    $options['pager'] = $this->t('Pager type');
    $options['hide_fields'] = $this->t('Hide fields');
    $options['sort_fields'] = $this->t('Reorder fields');
    $options['configure_filters'] = $this->t('Configure filters');
    $options['disable_filters'] = $this->t('Disable filters');
    $form['allow']['#options'] = $options;
    // update the items_per_page if set
    $defaults = array_filter($form['allow']['#default_value']);
    if (isset($defaults['items_per_page'])) {
      $defaults['items_per_page'] = 'items_per_page';
    }
    $form['allow']['#default_value'] = $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);

    $allow_settings = array_filter($this->getOption('allow'));
    $block_configuration = $block->getConfiguration();

    if (!empty($allow_settings['items_per_page'])) {
      // Items per page
      $form['override']['items_per_page']['#type'] = 'textfield';
      $form['override']['items_per_page']['#size'] = 4;
      unset($form['override']['items_per_page']['#options']);
    }

    if (!empty($allow_settings['offset'])) {
      // Pager offset
      $form['override']['pager_offset'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Pager offset'),
        '#size' => 4,
        '#default_value' => isset($block_configuration['pager_offset']) ? $block_configuration['pager_offset'] : 0,
      ];
    }

    if (!empty($allow_settings['pager'])) {
      // Pager settings
      $pager_options = [
        'view' => $this->t('Inherit from view'),
        'some' => $this->t('Display a specified number of items'),
        'none' => $this->t('Display all items')
      ];
      $form['override']['pager'] = [
        '#type' => 'radios',
        '#title' => $this->t('Pager'),
        '#options' => $pager_options,
        '#default_value' => isset($block_configuration['pager']) ? $block_configuration['pager'] : 'view'
      ];
    }

    if (!empty($allow_settings['hide_fields']) || !empty($allow_settings['sort_fields'])) {
      $field_options = [];
      $fields = $this->getOption('fields');
      $header = [];
      if (!empty($allow_settings['hide_fields'])) {
        $header['hide'] = $this->t('Hide');
      }
      $header['label'] = $this->t('Label');
      if (!empty($allow_settings['sort_fields'])) {
        $header['weight'] = $this->t('Weight');
      }
      $form['override']['order_fields'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => array(),
      ];
      if (!empty($allow_settings['sort_fields'])) {
        $form['override']['order_fields']['#tabledrag'] = [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'field-weight',
          ]
        ];
        $form['override']['order_fields']['#attributes'] = ['id' => 'order-fields'];
      }
      $sorted_fields = [];
      if (!empty($allow_settings['sort_fields']) && isset($block_configuration['fields'])) {
        foreach (array_keys($block_configuration['fields']) as $field_name) {
          if (!empty($fields[$field_name])) {
            $sorted_fields[$field_name] = $fields[$field_name];
            unset($fields[$field_name]);
          }
        }
        if (!empty($fields)) {
          foreach ($fields as $field_name => $field_info) {
            $sorted_fields[$field_name] = $field_info;
          }
        }
      }
      else {
        $sorted_fields = $fields;
      }
      foreach ($sorted_fields as $field_name => $field_info) {
        $field_options[$field_name] = $field_info['label'];
        if (!empty($allow_settings['sort_fields'])) {
          $form['override']['order_fields'][$field_name]['#attributes']['class'][] = 'draggable';
        }
        $form['override']['order_fields'][$field_name]['#weight'] = !empty($block_configuration['fields'][$field_name]['weight']) ? $block_configuration['fields'][$field_name]['weight'] : '';
        if (!empty($allow_settings['hide_fields'])) {
          $form['override']['order_fields'][$field_name]['hide'] = [
            '#type' => 'checkbox',
            '#default_value' => !empty($block_configuration['fields'][$field_name]['hide']) ? $block_configuration['fields'][$field_name]['hide'] : 0,
          ];
        }
        $form['override']['order_fields'][$field_name]['label'] = [
          '#markup' => $field_info['label'],
        ];
        if (!empty($allow_settings['sort_fields'])) {
          $form['override']['order_fields'][$field_name]['weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for @title', ['@title' => $field_info['label']]),
            '#title_display' => 'invisible',
            '#delta' => 50,
            '#default_value' => !empty($block_configuration['fields'][$field_name]['weight']) ? $block_configuration['fields'][$field_name]['weight'] : 0,
            '#attributes' => ['class' => ['field-weight']],
          ];
        }
      }
    }

    if (!empty($allow_settings['configure_filters']) || !empty($allow_settings['disable_filters'])) {
      $filters = $this->getOption('filters');
      $manager = \Drupal::service('plugin.manager.views.filter');
      foreach ($filters as $filter_name => $values) {
        if (!empty($values['exposed']) && $values['exposed']) {
          $form['override']['filters'][$filter_name] = [
            '#type' => 'details',
            '#title' => $values['expose']['label'],
          ];
          /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $plugin */
          $plugin = $manager->getHandler($values);
          $plugin->init($this->view, $this, $values);
          $form['override']['filters'][$filter_name]['plugin'] = [
            '#type' => 'value',
            '#value' => $plugin,
          ];
          if (!empty($allow_settings['configure_filters'])) {
            $plugin_form = [];
            $plugin->buildExposedForm($plugin_form, $form_state);
            $form['override']['filters'][$filter_name]['form'] = $plugin_form;
            if (!empty($block_configuration["filter"][$filter_name]['value'])) {
              $form['override']['filters'][$filter_name]['form'][$plugin->exposedInfo()['value']]['#default_value'] = $block_configuration["filter"][$filter_name]['value'];
            }
          }
          if (!empty($allow_settings['disable_filters'])) {
            $form['override']['filters'][$filter_name]['disable'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Disable'),
              '#default_value' => !empty($block_configuration['filter'][$filter_name]['disable']) ? $block_configuration['filter'][$filter_name]['disable'] : 0,
            ];
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    parent::blockSubmit($block, $form, $form_state);
    $configuration = $block->getConfiguration();
    $allow_settings = array_filter($this->getOption('allow'));
    // Pager settings.
    if (!empty($allow_settings['pager'])) {
      if ($pager = $form_state->getValue(['override', 'pager'])) {
        $configuration['pager'] = $pager;
      }
    }
    // Pager offset
    if (!empty($allow_settings['offset'])) {
      $configuration['pager_offset'] = $form_state->getValue(['override', 'pager_offset']);
    }
    // Hide/Sort Fields
    if (!empty($allow_settings['hide_fields']) || !empty($allow_settings['sort_fields'])) {
      if ($fields = array_filter($form_state->getValue(['override', 'order_fields']))) {
        $configuration['fields'] = $fields;
      }
    }

    // Handle exposed filters as configuration options.
    if (!empty($allow_settings['configure_filters']) || !empty($allow_settings['disable_filters'])) {
      if ($filters = $form_state->getValue(['override', 'filters'])) {
        foreach ($filters as $filter_name => $filter) {
          /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $plugin */
          $plugin = $form_state->getValue(['override', 'filters', $filter_name, 'plugin']);
          $form_value = $form_state->getValue(['override', 'filters', $filter_name, 'form', $plugin->exposedInfo()['value']]);
          // If we allow filter disabling.
          if (!empty($allow_settings['disable_filters'])) {
            $disable = $form_state->getValue(['override', 'filters', $filter_name, 'disable']);
            // if this is disabled, we don't really care about other stuff.
            if ($disable) {
              $configuration["filter"][$filter_name]['disable'] = $disable;
              continue;
            }
            elseif (!empty($configuration["filter"][$filter_name]['disable'])) {
              unset($configuration["filter"][$filter_name]['disable']);
            }
          }
          // If we allow filter configuration.
          if (!empty($allow_settings['configure_filters'])) {
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
      }
    }
    $block->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function preBlockBuild(ViewsBlock $block) {
    parent::preBlockBuild($block);

    $allow_settings = array_filter($this->getOption('allow'));
    $config = $block->getConfiguration();
    list(, $display_id) = explode('-', $block->getDerivativeId());
    // Offset configuration.
    if (!empty($allow_settings['offset'])) {
      $this->view->setOffset($config['pager_offset']);
    }
    // Pager style
    if (!empty($allow_settings['pager'])) {
      $pager = $this->view->display_handler->getOption('pager');
      if ($config['pager'] != 'view') {
        $pager['type'] = $config['pager'];
      }
      $this->view->display_handler->setOption('pager', $pager);
    }
    // Field configuration.
    if (!empty($allow_settings['hide_fields']) || !empty($allow_settings['sort_fields'])) {
      if (!empty($config['fields']) && $this->view->getStyle()->usesFields()) {
        $fields = $this->view->getHandlers('field');
        $iterate_fields = !empty($allow_settings['sort_fields']) ? $config['fields'] : $fields;
        foreach (array_keys($iterate_fields) as $field_name) {
          // Remove each field in sequence and re-add them to sort
          // appropriately or hide if disabled.
          $this->view->removeHandler($display_id, 'field', $field_name);
          if (empty($allow_settings['hide_fields']) || (!empty($allow_settings['hide_fields']) && empty($config['fields'][$field_name]['hide']))) {
            $this->view->addHandler($display_id, 'field', $fields[$field_name]['table'], $field_name, $fields[$field_name]);
          }
        }
      }
    }
    // Filter configuration.
    if (!empty($allow_settings['disable_filters']) || !empty($allow_settings['configure_filters'])) {
      $filters = $this->view->getHandlers('filter', $display_id);
      foreach ($filters as $filter_name => $filter) {
        // If we allow disabled filters and this filter is disabled, disable it
        // and continue.
        if (!empty($allow_settings['disable_filters']) && !empty($config["filter"][$filter_name]['disable'])) {
          $this->view->removeHandler($display_id, 'filter', $filter_name);
          continue;
        }
        // If the filter is not disabled and we allow filter configuration and
        // we have a value in config, set the filter value, and un-expose it.
        if (!empty($allow_settings['configure_filters']) && !empty($config["filter"][$filter_name]['value'])) {
          $filter['value'] = $this->getValueByOperator($filter['operator'], $config["filter"][$filter_name]['value']);
          unset($filter['exposed']);
          $this->view->setHandler($display_id, 'filter', $filter_name, $filter);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
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
  protected function getValueByOperator($operator, $value) {
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
