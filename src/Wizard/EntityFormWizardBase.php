<?php

/**
 * @file
 * Contains \Drupal\ctools\Wizard\EntityFormWizardBase.
 */

namespace Drupal\ctools\Wizard;


use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Event\WizardEvent;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The base class for all entity form wizards.
 */
abstract class EntityFormWizardBase extends FormWizardBase implements EntityFormWizardInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param $tempstore_id
   *   The shared temp store factory collection name.
   * @param null $machine_name
   *   The SharedTempStore key for our current wizard values.
   * @param null $step
   *   The current active step of the wizard.
   */
  public function __construct(SharedTempStoreFactory $tempstore, FormBuilderInterface $builder, ClassResolverInterface $class_resolver, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, $tempstore_id, $machine_name = NULL, $step = NULL) {
    $this->entityManager = $entity_manager;
    parent::__construct($tempstore, $builder, $class_resolver, $event_dispatcher, $tempstore_id, $machine_name, $step);
  }

  /**
   * {@inheritdoc}
   */
  public static function getParameters() {
    return [
      'tempstore' => \Drupal::service('user.shared_tempstore'),
      'builder' => \Drupal::service('form_builder'),
      'class_resolver' => \Drupal::service('class_resolver'),
      'event_dispatcher' => \Drupal::service('event_dispatcher'),
      'entity_manager' => \Drupal::service('entity.manager'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareValues() {
    $values = [];
    if ($this->getMachineName()) {
      $entity = $this->entityManager->getStorage($this->getEntityType())->load($this->getMachineName());
      if ($entity) {
        $values = $entity->toArray();
      }
    }
    $event = new WizardEvent($this, $values);
    $this->dispatcher->dispatch(FormWizardInterface::LOAD_VALUES, $event);
    $this->initValues($event->getValues());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    $entity = $this->entityManager->getStorage($this->getEntityType())->load($this->machine_name);
    if (!$entity) {
      $entity = $this->entityManager->getStorage($this->getEntityType())->create([]);
    }
    foreach ($form_state->getTemporaryValue('wizard') as $key => $value) {
      $entity->set($key, $value);
    }
    $status = $entity->save();
    $definition = $this->entityManager->getDefinition($this->getEntityType());
    if ($status) {
      drupal_set_message($this->t('Saved the %label !entity_type.', array(
        '%label' => $entity->label(),
        '!entity_type' => $definition->getLabel(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label !entity_type was not saved.', array(
        '%label' => $entity->label(),
        '!entity_type' => $definition->getLabel(),
      )));
    }
    $form_state->setRedirectUrl($entity->urlInfo('collection'));
    parent::finish($form, $form_state);
  }

  /**
   * Helper function for generating label and id form elements.
   */
  protected function getDefaultFormElements($cached_values) {
    // Get the plugin definition of this entity.
    $definition = $this->entityManager->getDefinition($this->getEntityType());
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
        'source' => array('name', 'label'),
        'exists' => $this->exists(),
      ),
      '#description' => $this->t('A unique machine-readable name for this !entity_type. It must only contain lowercase letters, numbers, and underscores.', ['!entity_type' => $definition->getLabel()]),
      '#default_value' => !empty($cached_values['id']) ? $cached_values['id'] : '',
      '#disabled' => !empty($cached_values['id']),
    );

    return $form;
  }
}
