<?php

/**
 * @file
 * Hooks provided by the Organic Groups module.
 */

declare(strict_types = 1);

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\og\OgAccess;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to alter group level permissions.
 *
 * @param array $permissions
 *   The list of group level permissions, passed by reference.
 * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
 *   The cache metadata.
 * @param array $context
 *   An associative array containing contextual information, with keys:
 *   - 'permission': The group level permission being checked, as a string.
 *   - 'group': The group entity on which the permission applies.
 *   - 'user': The user account for which access is being determined.
 */
function hook_og_user_access_alter(array &$permissions, CacheableMetadata $cacheable_metadata, array $context): void {
  // This example implements a use case where a custom module allows site
  // builders to toggle a configuration setting that will prevent groups to be
  // deleted if they are published.
  // Retrieve the module configuration.
  $config = \Drupal::config('mymodule.settings');

  // Check if the site is configured to allow deletion of published groups.
  $published_groups_can_be_deleted = $config->get('delete_published_groups');

  // If deletion is not allowed and the group is published, revoke the
  // permission.
  $group = $context['group'];
  if ($group instanceof EntityPublishedInterface && !$group->isPublished() && !$published_groups_can_be_deleted) {
    $key = array_search(OgAccess::DELETE_GROUP_PERMISSION, $permissions);
    if ($key !== FALSE) {
      unset($permissions[$key]);
    }
  }

  // Since our access result depends on our custom module configuration, we need
  // to add it to the cache metadata.
  $cacheable_metadata->addCacheableDependency($config);
}

/**
 * @} End of "addtogroup hooks".
 */
