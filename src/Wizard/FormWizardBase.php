<?php

/**
 * @file
 * Contains \Drupal\ctools\Wizard\FormWizardBase.
 */

namespace Drupal\ctools\Wizard;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\SharedTempStoreFactory;

/**
 * The base class for all form wizard.
 */
abstract class FormWizardBase extends FormBase implements FormWizardInterface {

  /**
   * Tempstore Factory for keeping track of values in each step of the wizard.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $builder;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface;
   */
  protected $classResolver;

  /**
   * @var string
   */
  protected $tempstore_id;

  /**
   * @var string
   */
  protected $machine_name;

  /**
   * @var string
   */
  protected $step;

  public function __construct(SharedTempStoreFactory $tempstore, FormBuilderInterface $builder, ClassResolverInterface $class_resolver, $tempstore_id, $machine_name = NULL, $step = NULL) {
    $this->tempstore = $tempstore;
    $this->builder = $builder;
    $this->classResolver = $class_resolver;
    $this->tempstore_id = $tempstore_id;
    $this->machine_name = $machine_name;
    $this->step = $step;
  }

  /**
   * {@inheritdoc}
   */
  public function getTempstoreId() {
    return $this->tempstore_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTempstore() {
    return $this->tempstore->get($this->getTempstoreId());
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getStep($cached_values) {
    if (!$this->step) {
      $operations = $this->getOperations();
      $steps = array_keys($operations);
      $this->step = reset($steps);
    }
    return $this->step;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperation($cached_values) {
    $operations = $this->getOperations();
    $step = $this->getStep($cached_values);
    if (!empty($operations[$step])) {
      return $operations[$step];
    }
    return reset($operations);
  }

  /**
   * {@inheritdoc}
   */
  public function getNextParameters($cached_values) {
    // Get the steps by key.
    $operations = $this->getOperations();
    $steps = array_keys($operations);
    // Get the steps after the current step.
    $after = array_slice($operations, array_search($this->getStep($cached_values), $steps) + 1);
    // Get the steps after the current step by key.
    $step = reset(array_keys($after));
    return [
      'machine_name' => $this->getMachineName(),
      'step' => $step,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousParameters($cached_values) {
    $operations = $this->getOperations();
    $step = $this->getStep($cached_values);

    // Get the steps by key.
    $steps = array_keys($operations);
    // Get the steps before the current step.
    $before = array_slice($operations, 0, array_search($step, $steps));
    // Get the steps before the current step by key.
    $before = array_keys($before);
    // Reverse the steps for easy access to the next step.
    $step = reset(array_reverse($before));
    return [
      'machine_name' => $this->getMachineName(),
      'step' => $step,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $cached_values = $this->getTempstore()->get($this->getMachineName());
    $operation = $this->getOperation($cached_values);
    /* @var $operation \Drupal\Core\Form\FormInterface */
    $operation = $this->classResolver->getInstanceFromDefinition($operation);
    return $operation->getFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the cached values out of the tempstore.
    $cached_values = $this->getTempstore()->get($this->getMachineName());
    $form_state->set('wizard', $cached_values);

    // Get the current form operation.
    $operation = $this->getOperation($cached_values);
    $default_operation = reset($this->getOperations());
    if ($operation == $default_operation) {
      $form = $this->getDefaultFormElements($cached_values);
    }
    /* @var $operation \Drupal\Core\Form\FormInterface */
    $operation = $this->classResolver->getInstanceFromDefinition($operation);
    $form = $operation->buildForm($form, $form_state);
    $form['actions'] = $this->actions($operation, $cached_values);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->get('wizard');
    if (is_null($this->getMachineName())) {
      $cached_values['label'] = $form_state->getValue('label');
      $cached_values['id'] = $form_state->getValue('id');
      $this->machine_name = $cached_values['id'];
      $this->getTempstore()->set($this->getMachineName(), $cached_values);
    }
    if ($form_state->getValue('op') == 'Next') {
      $form_state->setRedirect($this->getRouteName(), $this->getNextParameters($cached_values));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function previous(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->get('wizard');
    $form_state->setRedirect($this->getRouteName(), $this->getPreviousParameters($cached_values));
  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    $this->getTempstore()->delete($this->getMachineName());
  }

  /**
   * Helper function for generating label and id form elements.
   *
   * @param mixed $cached_values
   *   The values returned by $this->getTempstore()->get($this->getMachineName());
   *
   * @return array
   */
  protected function getDefaultFormElements($cached_values) {
    // Create id and label form elements.
    $form['name'] = array(
      '#type' => 'fieldset',
      '#attributes' => array('class' => array('fieldset-no-legend')),
      '#title' => $this->getWizardLabel(),
    );
    $form['name']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->getMachineLabel(),
      '#required' => TRUE,
      '#size' => 32,
      '#default_value' => !empty($cached_values['label']) ? $cached_values['label'] : '',
      '#maxlength' => 255,
      '#disabled' => !empty($cached_values['label']),
    );
    $form['name']['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => array(
        'exists' => 'page_manager_display_load',
        'source' => array('name', 'label'),
      ),
      '#description' => t('A unique machine-readable name for this View. It must only contain lowercase letters, numbers, and underscores.'),
      '#default_value' => !empty($cached_values['id']) ? $cached_values['id'] : '',
      '#disabled' => !empty($cached_values['id']),
    );

    return $form;
  }

  /**
   * Generates action elements for navigating between the operation steps.
   *
   * @param \Drupal\Core\Form\FormInterface $form
   *   The current operation form.
   * @param $cached_values
   *   The values returned by $this->getTempstore()->get($this->getMachineName());
   *
   * @return array
   */
  protected function actions(FormInterface $form, $cached_values) {
    $operations = $this->getOperations();
    $step = $this->getStep($cached_values);

    $steps = array_keys($operations);
    // Slice to find the operations that occur before the current operation.
    $before = array_slice($operations, 0, array_search($step, $steps));
    // Slice to find the operations that occur after the current operation.
    $after = array_slice($operations, array_search($step, $steps) + 1);

    $actions = array(
      'submit' => array(
        '#value' => $this->t('Next'),
        '#validate' => array(
          array($form, 'validateForm'),
          array($this, 'validateForm'),
        ),
        '#submit' => array(
          array($form, 'submitForm'),
          array($this, 'submitForm'),
        ),
      ),
    );

    // If there are not steps after this one, label the button "Finish".
    if (!$after) {
      $actions['submit']['#value'] = t('Finish');
      $actions['submit']['#submit'][] = array($this, 'finish');
    }

    // If there are steps before this one, label the button "previous"
    // otherwise do not display a button.
    if ($before) {
      $actions['previous'] = array(
        '#value' => t('Previous'),
        '#submit' => array(
          array($this, 'previous'),
        ),
        '#limit_validation_errors' => array(),
        '#weight' => -10,
      );
    }

    foreach ($actions as &$action) {
      $action['#type'] = 'submit';
    }
    return $actions;
  }

}
