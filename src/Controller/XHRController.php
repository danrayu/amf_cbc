<?php

namespace Drupal\amf_cbc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\amf_cbc\CBCHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Http\RequestStack;

/**
 * Controller handling XHR calls..
 */
class XHRController extends ControllerBase {

  /**
   * AMF CBC Helper.
   *
   * @var \Drupal\amf_cbc\CBCHelper
   */
  protected $amfCBCApp;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new XHRController object.
   *
   * @param \Drupal\amf_cbc\CBCHelper $amf_cbc
   *   Amf CBC Helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Http\RequestStack $request_stack
   *   Current request.
   */
  public function __construct(CBCHelper $amf_cbc, EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack) {
    $this->amfCBCApp = $amf_cbc;
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('amf_cbc'),
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * XHR handler.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Operation response..
   */
  public function xhr() {
    $result = 0;
    $params = $this->request->request->all();
    if (!empty($params['action']) && !empty($params['values'])) {
      $values = json_decode($params['values'], TRUE);
      switch ($params['action']) {

        // Handle the form submit.
        case 'submit':
          $values['submission_data']['calculator_data'] = json_decode($values['submission_data']['calculator_data'], TRUE) ?: NULL;
          if ($this->amfCBCApp->executeRequests($values)) {
            $result = 1;
          }

          break;
      }
    }

    return new JsonResponse($result);
  }

}
