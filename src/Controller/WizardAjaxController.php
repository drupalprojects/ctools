<?php
/**
 * @file
 * Contains \Drupal\ctools\Controller\WizardAjaxController.
 */

namespace Drupal\ctools\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\system\Controller\FormAjaxController;
use Drupal\system\FileAjaxForm;
use Drupal\user\SharedTempStoreFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class WizardAjaxController extends FormAjaxController implements ContainerInjectionInterface {

  /**
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * Constructs a FormAjaxController object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Render\MainContent\MainContentRendererInterface $ajax_renderer
   *   The main content to AJAX Response renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   */
  public function __construct(LoggerInterface $logger, FormBuilderInterface $form_builder, RendererInterface $renderer, MainContentRendererInterface $ajax_renderer, RouteMatchInterface $route_match, SharedTempStoreFactory $tempstore) {
    $this->tempstore = $tempstore;
    parent::__construct($logger, $form_builder, $renderer, $ajax_renderer, $route_match);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('ajax'),
      $container->get('form_builder'),
      $container->get('renderer'),
      $container->get('main_content_renderer.ajax'),
      $container->get('current_route_match'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getForm(Request $request) {
    $ajax_form = parent::getForm($request);
    $form_state = $ajax_form->getFormState();
    $form_state->setTemporaryValue('wizard', $this->tempstore->get($request->attributes->get('tempstore_id'))->get($request->attributes->get('machine_name')));
    return new FileAjaxForm($ajax_form->getForm(), $form_state, $ajax_form->getFormId(), $ajax_form->getFormBuildId(), $ajax_form->getCommands());
  }

}
