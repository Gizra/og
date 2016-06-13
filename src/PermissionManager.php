<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Manager for OG permissions.
 *
 * @todo Provide an interface.
 */
class PermissionManager {

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
   * The service providing information about bundles.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a PermissionManager object.
   *
   * @param \Drupal\og\GroupManager $group_manager
   *   The OG group manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The service providing information about bundles.
   */
  public function __construct(GroupManager $group_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->groupManager = $group_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * Generates the OG permission list for the given group type.
   *
   * @param string $entity_type_id
   *   The entity type ID of the group for which to generate the permissions.
   * @param string $bundle_id
   *   The bundle ID of the group for which to generate the permissions.
   *
   * @return array
   *   The list of permissions.
   */
  public function getPermissionList($entity_type_id, $bundle_id) {
    $permissions = [];

    foreach ($this->groupManager->getGroupContentBundleIdsByGroupBundle($entity_type_id, $bundle_id) as $group_content_entity_type_id => $group_content_bundle_ids) {
      foreach ($group_content_bundle_ids as $group_content_bundle_id) {
        $permissions += $this->getEntityOperationPermissions($group_content_entity_type_id, $group_content_bundle_id);
      }
    }

    return $permissions;
  }

  /**
   * Returns permissions related to entity operations for a given bundle.
   *
   * @param $group_content_entity_type_id
   *   The entity type ID for which to generate the permission list.
   * @param $group_content_bundle_id
   *   The bundle ID for which to generate the permission list.
   * @param bool $is_owner
   *   Boolean indication whether or not the permissions are being retrieved for
   *   a user that is the owner of the entity in question.
   *
   * @return array
   *   An array of permission names and descriptions, keyed by operation.
   */
  public function getEntityOperationPermissions($group_content_entity_type_id, $group_content_bundle_id, $is_owner = TRUE) {
    // Check if the bundle is a group content type.
    if (!Og::isGroupContent($group_content_entity_type_id, $group_content_bundle_id)) {
      return [];
    }

    $entity_info = $this->entityTypeManager->getDefinition($group_content_entity_type_id);
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($group_content_entity_type_id)[$group_content_bundle_id];

    // Build standard list of permissions for this bundle.
    $args = [
      '%bundle' => $bundle_info['label'],
      '@entity' => $entity_info->getPluralLabel(),
    ];
    // @todo This needs to support all entity operations for the given entity
    //    type, not just the standard CRUD operations.
    // @see https://github.com/amitaibu/og/issues/222
    $permissions = [
      "create $group_content_bundle_id $group_content_entity_type_id" => [
        'title' => t('Create %bundle @entity', $args),
        'operation' => 'create',
      ],
      "update any $group_content_bundle_id $group_content_entity_type_id" => [
        'title' => t('Edit any %bundle @entity', $args),
        'operation' => 'update',
      ],
      "delete any $group_content_bundle_id $group_content_entity_type_id" => [
        'title' => t('Delete any %bundle @entity', $args),
        'operation' => 'delete',
      ],
    ];

    // Add the permissions for the owner of the entity if needed.
    if ($is_owner) {
      $permissions["update own $group_content_bundle_id $group_content_entity_type_id"] = [
        'title' => t('Edit own %bundle @entity', $args),
        'operation' => 'update',
        'ownership' => 'own',
      ];
      $permissions["delete own $group_content_bundle_id $group_content_entity_type_id"] = [
        'title' => t('Delete own %bundle @entity', $args),
        'operation' => 'delete',
        'ownership' => 'own',
      ];
    }

    foreach ($permissions as &$permission) {
      // Set the entity type and bundle IDs of the group content to which this
      // operation applies.
      $permission['entity type'] = $group_content_entity_type_id;
      $permission['bundle'] = $group_content_bundle_id;
      // Enable each permission for the administrator role by default.
      $permission['default role'] = [OgRoleInterface::ADMINISTRATOR];
    }

    return $permissions;
  }

}
