<?php

namespace Drupal\og_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\og\GroupManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class OgUiController extends ControllerBase {

  /**
   * The OG group manager.
   *
   * @var \Drupal\og\GroupManager
   */
  protected $groupManager;

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
   * Constructs a OgUiController object.
   *
   * @param \Drupal\og\GroupManager $group_manager
   *   The OG group manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(GroupManager $group_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->groupManager = $group_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.group.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }
  
  /**
   * Returns the overview of OG roles and permissions.
   *
   * @param string $type
   *   The type of overview, either 'roles' or 'permissions'.
   *
   * @return array
   *   The overview as a render array.
   */
  public function rolesPermissionsOverviewPage($type) {
    $action = $type === 'roles' ? t('Edit roles') : t('Edit permissions');
    $header = [t('Group type'), t('Operations')];
    $rows = [];

    foreach ($this->groupManager->getAllGroupBundles() as $entity_type => $bundles) {
      $definition = $this->entityTypeManager->getDefinition($entity_type);
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      foreach ($bundles as $bundle) {
        $rows[] = [
          [
            'data' => $definition->getLabel() . ' - ' . $bundle_info[$bundle]['label'],
          ],
          [
            'data' => Link::createFromRoute($action, 'og_ui.' . $type . '_form', [
              'entity_type' => $entity_type,
              'bundle' => $bundle,
            ]),
          ],
        ];
      }
    }

    $build['roles_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No group types available.'),
    ];

    return $build;
  }

  /**
   * Title callback for rolesPermissionsOverviewPage.
   *
   * @param string $type
   *   The type of overview, either 'roles' or 'permissions'.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function rolesPermissionsOverviewTitleCallback($type) {
    return $this->t('OG @type overview', ['@type' => $type]);
  }
  
}
