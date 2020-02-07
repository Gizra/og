<?php

namespace Drupal\og\Plugin\OgGroupResolver;

use Drupal\og\OgGroupResolverBase;
use Drupal\og\OgResolvedGroupCollectionInterface;

/**
 * Checks that the current user has access to the resolved groups.
 *
 * This plugin loops over all groups that were found by previous plugins and
 * removes the ones that are not accessible by the current user. It is
 * recommended to place this plugin at the end of the list of plugins.
 *
 * @OgGroupResolver(
 *   id = "user_access",
 *   label = "Current user has access to group",
 *   description = @Translation("Filters the resolved groups, keeping the ones the current user has access to.")
 * )
 */
class UserGroupAccessResolver extends OgGroupResolverBase {

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {
    foreach ($collection->getGroupInfo() as $group_info) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $group */
      $group = $group_info['entity'];

      // If the current user has access, cast a vote along with the 'user'
      // cache context, since the current user affects the outcome of the final
      // result.
      if ($group->access('view')) {
        $collection->addGroup($group, ['user']);
      }
      else {
        // The user doesn't have access. Remove the group from the collection.
        $collection->removeGroup($group);
      }
    }
  }

}
