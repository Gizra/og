<?php

declare(strict_types = 1);

namespace Drupal\og_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\Og;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    $action = $type === 'roles' ? $this->t('Edit roles') : $this->t('Edit permissions');
    $header = [$this->t('Group type'), $this->t('Operations')];
    $rows = [];
    $build = [];

    foreach ($this->groupTypeManager->getGroupMap() as $entity_type => $bundles) {
      $definition = $this->entityTypeManager->getDefinition($entity_type);
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      foreach ($bundles as $bundle) {
        $rows[] = [
          [
            'data' => $definition->getLabel() . ' - ' . $bundle_info[$bundle]['label'],
          ],
          [
            'data' => Link::createFromRoute($action, 'og_ui.' . $type . '_overview', [
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
   * Returns the OG role overview page for a given entity type and bundle.
   *
   * @param string $entity_type
   *   The entity type for which to show the OG roles.
   * @param string $bundle
   *   The bundle for which to show the OG roles.
   *
   * @return array
   *   The overview as a render array.
   */
  public function rolesOverviewPage($entity_type = '', $bundle = '') {
    // Return a 404 error when this is not a group.
    if (!Og::isGroup($entity_type, $bundle)) {
      throw new NotFoundHttpException();
    }

    $rows = [];

    $properties = [
      'group_type' => $entity_type,
      'group_bundle' => $bundle,
    ];
    /** @var \Drupal\og\Entity\OgRole $role */
    foreach ($this->entityTypeManager->getStorage('og_role')->loadByProperties($properties) as $role) {
      // Add the role name cell.
      $columns = [['data' => $role->getLabel()]];

      // Add the edit role link if the role is editable.
      if (!$role->isLocked()) {
        $operations = [];
        // if ($role->access('update') && $role->hasLinkTemplate('edit-form')) {
        if ($role->hasLinkTemplate('edit-form')) {
          $operations['edit'] = [
            'title' => $this->t('Edit role'),
            'weight' => 10,
            'url' => $role->urlInfo('edit-form'),
          ];
        }
        //if ($role->access('delete') && $role->hasLinkTemplate('delete-form')) {
        if ($role->hasLinkTemplate('delete-form')) {
          $operations['delete'] = [
            'title' => $this->t('Delete role'),
            'weight' => 100,
            'url' => $role->urlInfo('delete-form'),
          ];
        }

        $columns[] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $operations,
          ],
        ];
      }
      else {
        $columns[] = ['data' => $this->t('Locked')];
      }

      // Add the edit permissions link.
      $columns[] = [
        'data' => Link::createFromRoute($this->t('Edit permissions'), 'og_ui.permissions_edit_form', [
          'og_role' => $role->id(),
        ]),
      ];

      $rows[] = $columns;
    }

    return [
      '#theme' => 'table',
      '#header' => [
        $this->t('Role name'),
        ['data' => $this->t('Operations'), 'colspan' => 2],
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No roles available.'),
    ];
  }

  /**
   * Title callback for the roles overview page.
   *
   * @param string $entity_type
   *   The group entity type.
   * @param string $bundle
   *   The group bundle.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function rolesOverviewPageTitleCallback($entity_type, $bundle) {
    return $this->t('OG @type - @bundle roles', [
      '@type' => $this->entityTypeManager->getDefinition($entity_type)->getLabel(),
      '@bundle' => $this->entityTypeBundleInfo->getBundleInfo($entity_type)[$bundle]['label'],
    ]);
  }

}
