<?php

declare(strict_types = 1);

namespace Drupal\og_ui\Controller;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\og\GroupTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The OG UI controller.
 */
class OgUiController extends ControllerBase {

  /**
   * The OG group manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
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
   * Constructs a OgUiController object.
   *
   * @param \Drupal\og\GroupTypeManagerInterface $group_manager
   *   The OG group manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(GroupTypeManagerInterface $group_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
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
   * Returns the overview of OG roles and permissions.
   *
   * @param string $type
   *   The type of overview, either 'roles' or 'permissions'.
   *
   * @return array
   *   The overview as a render array.
   */
  public function rolesPermissionsOverviewPage($type) {
    $route = $type === 'roles' ? 'entity.og_role.collection' : 'og_ui.permissions_overview';
    $action = $type === 'roles' ? $this->t('Edit roles') : $this->t('Edit permissions');
    $header = [$this->t('Group type'), $this->t('Operations')];
    $rows = [];
    $build = [];

    foreach ($this->groupTypeManager->getGroupMap() as $entity_type => $bundles) {
      try {
        $definition = $this->entityTypeManager->getDefinition($entity_type);
      }
      catch (PluginNotFoundException $e) {
        // The entity type manager might throw this exception if the entity type
        // is not defined. If this happens it means there is a discrepancy
        // between the group types in config, and the modules that providing
        // these entity types. This is not something we can rectify here but it
        // does not block the rendering of the page. In the rare case that this
        // occurs, let's log an error and exclude the entity type from the page.
        $this->getLogger('og')->error('Error: the %entity_type entity type is not defined but is supposed to have group bundles.', ['%entity_type' => $entity_type]);
        continue;
      }

      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      foreach ($bundles as $bundle) {
        $rows[] = [
          [
            'data' => $definition->getLabel() . ' - ' . $bundle_info[$bundle]['label'],
          ],
          [
            'data' => Link::createFromRoute($action, $route, [
              'entity_type_id' => $entity_type,
              'bundle_id' => $bundle,
            ]),
          ],
        ];
      }
    }

    $build['roles_permissions_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No group types available.'),
    ];

    return $build;
  }

  /**
   * Title callback for rolesPermissionsOverviewPage().
   *
   * @param string $type
   *   The type of overview, either 'roles' or 'permissions'.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Return the translated title.
   */
  public function rolesPermissionsOverviewTitleCallback($type) {
    return $this->t('OG @type overview', ['@type' => $type]);
  }

  /**
   * Title callback for the roles overview page.
   *
   * @param string $entity_type_id
   *   The group entity type ID.
   * @param string $bundle_id
   *   The group bundle ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The roles overview page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the entity type with the given ID is not defined.
   */
  public function rolesOverviewPageTitleCallback($entity_type_id, $bundle_id) {
    return $this->t('OG @type - @bundle roles', [
      '@type' => $this->entityTypeManager->getDefinition($entity_type_id)->getLabel(),
      '@bundle' => $this->entityTypeBundleInfo->getBundleInfo($entity_type_id)[$bundle_id]['label'],
    ]);
  }

}
