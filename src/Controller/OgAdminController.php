<?php

namespace Drupal\og\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\GroupManager;
use Drupal\og\GroupTypeManager;
use Drupal\og_ui\OgUi;
use Drupal\og_ui\OgAdminRouteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The OG admin controller.
 */
class OgAdminController extends ControllerBase {

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs an OgAdminController object.
   *
   * @param \Drupal\og\GroupTypeManager $group_manager
   *   The OG group manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(GroupTypeManager $group_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->groupTypeManager = $group_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.group_type_manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Show all the available admin routes.
   *
   * @return mixed
   *   List of available admin routes for the current group.
   */
  public function mainPage() {
    // Get list from routes.
    $content = [];

    return [
      'og_admin_routes' => [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ],
    ];
  }

}
