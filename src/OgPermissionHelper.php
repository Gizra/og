<?php

namespace Drupal\og;

/**
 * Contains helper methods for working with OG permissions.
 */
class OgPermissionHelper {

  /**
   * Helper function to generate a standard permission list for a given bundle.
   *
   * @param $entity_type_id
   *   The entity type ID for which to generate the permission list.
   * @param $bundle_id
   *   The bundle ID for which to generate the permission list.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  public static function generatePermissionList($entity_type_id, $bundle_id) {
    $permissions = [];

    // Check if the bundle is a group content type.
    if (Og::isGroupContent($entity_type_id, $bundle_id)) {
      $entity_info = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
      $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id)[$bundle_id];

      // Build standard list of permissions for this bundle.
      $args = [
        '%bundle' => $bundle_info['label'],
        '@entity' => $entity_info->getPluralLabel(),
      ];
      $permissions += array(
        "create $bundle_id $entity_type_id" => [
          'title' => t('Create %bundle @entity', $args),
        ],
        "update own $bundle_id $entity_type_id" => [
          'title' => t('Edit own %bundle @entity', $args),
        ],
        "update any $bundle_id $entity_type_id" => [
          'title' => t('Edit any %bundle @entity', $args),
        ],
        "delete own $bundle_id $entity_type_id" => [
          'title' => t('Delete own %bundle @entity', $args),
        ],
        "delete any $bundle_id $entity_type_id" => [
          'title' => t('Delete any %bundle @entity', $args),
        ],
      );

      // Add default permissions.
      foreach ($permissions as $key => $value) {
        $permissions[$key]['default role'] = [OgRoleInterface::ADMINISTRATOR];
      }
    }

    return $permissions;
  }

}
