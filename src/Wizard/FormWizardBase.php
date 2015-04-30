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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * The Form Builder.
   *
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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The shared temp store factory collection name.
   *
   * @var string
   */
  protected $tempstore_id;

  /**
   * The SharedTempStore key for our current wizard values.
   *
   * @var string|NULL
   */
  protected $machine_name;

  /**
   * The current active step of the wizard.
   *
   * @var string|NULL
   */
  protected $step;

  /**
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   Tempstore Factory for keeping track of values in each step of the
   *   wizard.
   * @param \Drupal\Core\Form\FormBuilderInterface $builder
   *   The Form Builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param $tempstore_id
   *   The shared temp store factory collection name.
   * @param null $machine_name
   *   The SharedTempStore key for our current wizard values.
   * @param null $step
   *   The current active step of the wizard.
   */
  public function __construct(SharedTempStoreFactory $tempstore, FormBuilderInterface $builder, ClassResolverInterface $class_resolver, EventDispatcherInterface $event_dispatcher, $tempstore_id, $machine_name = NULL, $step = NULL) {
    $this->tempstore = $tempstore;
    $this->builder = $builder;
    $this->classResolver = $class_resolver;
    $this->dispatcher = $event_dispatcher;
    $this->tempstore_id = $tempstore_id;
    $this->machine_name = $machine_name;
    $this->step = $step;
  }

  public function initValues($values) {
    if ($this->getMachineName() && !$this->getTempstore()->get($this->getMachineName())) {
      $this->getTempstore()->set($this->getMachineName(), $values);
    }
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
    $operation = reset($operations);
    return $operation;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextOp() {
    return $this->t('Next');
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
    $after_keys = array_keys($after);
    $step = reset($after_keys);
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
    $before_steps = array_reverse($before);
    $step = reset($before_steps);
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
    $operation = $this->classResolver->getInstanceFromDefinition($operation['form']);
    return $operation->getFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the cached values out of the tempstore.
    $cached_values = $this->getTempstore()->get($this->getMachineName());
    $form_state->setTemporaryValue('wizard', $cached_values);

    // Get the current form operation.
    $operation = $this->getOperation($cached_values);
    $operations = $this->getOperations();
    $default_operation = reset($operations);
    if ($operation['form'] == $default_operation['form']) {
      $form = $this->getDefaultFormElements($cached_values);
    }
    /* @var $formClass \Drupal\Core\Form\FormInterface */
    $formClass = $this->classResolver->getInstanceFromDefinition($operation['form']);
    $form = $formClass->buildForm($form, $form_state);
    if (isset($operation['title'])) {
      $form['#title'] = $operation['title'];
    }
    $form['actions'] = $this->actions($formClass, $cached_values);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Only perform this logic if we're moving to the next page. This prevents
    // the loss of cached values on ajax submissions.
    if ($form_state->getValue('op') == $this->getNextOp()) {
      $cached_values = $form_state->getTemporaryValue('wizard');
      if ($form_state->hasValue('label')) {
        $cached_values['label'] = $form_state->getValue('label');
      }
      if ($form_state->hasValue('id')) {
        $cached_values['id'] = $form_state->getValue('id');
      }
      if (is_null($this->machine_name) && !empty($cached_values['id'])) {
        $this->machine_name = $cached_values['id'];
      }
      $form_state->setRedirect($this->getRouteName(), $this->getNextParameters($cached_values));
      $this->getTempstore()->set($this->getMachineName(), $cached_values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function populateCachedValues(array &$form, FormStateInterface $form_state) {
    $cached_values = $this->getTempstore()->get($this->getMachineName());
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

  /**
   * {@inheritdoc}
   */
  public function previous(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $form_state->setRedirect($this->getRouteName(), $this->getPreviousParameters($cached_values));
  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    $this->getTempstore()->delete($this->getMachineName());
  }

  /**
   * Helper function for generating default form elements.
   *
   * @param mixed $cached_values
   *   The values returned by $this->getTempstore()->get($this->getMachineName());
   *
   * @return array
   */
  protected function getDefaultFormElements($cached_values) {
    return [];
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
          array($this, 'populateCachedValues'),
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
        '#validate' => array(
          array($this, 'populateCachedValues'),
        ),
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
