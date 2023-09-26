<?php

/**
 * @file
 * Hooks provided by the Organic Groups module.
 */

declare(strict_types = 1);

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Form\FormStateInterface;
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
 * Implements hook_form_FORM_ID_alter().
 */
function hook_form_og_membership_remove_multiple_roles_action_form_alter(array &$form, FormStateInterface $form_state, string $form_id) {
  // Get access to current group and selected memberships when we're on the role
  // remove form.
  /** @var \Drupal\og\OgMembershipInterface[] $memberships */
  $memberships = $form_state->getTemporaryValue('selected_memberships');

  $form['roles']['#options'] = array_filter($form['roles']['#options'], function ($key) use (&$form, $memberships) {
    // Code to filter out options based on data from memberships and group.
    $membership = reset($memberships);
    $group = $membership->getGroup();
    if ($group->id() != 9999) {
      unset($form['roles']['#options'][$key]);
    }
  }, ARRAY_FILTER_USE_KEY);
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function hook_form_og_membership_add_multiple_roles_action_form_alter(array &$form, FormStateInterface $form_state, string $form_id) {
  // Get access to current group and selected memberships when we're on the role
  // add form.
  /** @var \Drupal\og\OgMembershipInterface[] $memberships */
  $memberships = $form_state->getTemporaryValue('selected_memberships');

  $form['roles']['#options'] = array_filter($form['roles']['#options'], function ($key) use (&$form, $memberships) {
    // Code to filter out options based on data from memberships and group.
    $membership = reset($memberships);
    $group = $membership->getGroup();
    if ($group->id() != 9999) {
      unset($form['roles']['#options'][$key]);
    }
  }, ARRAY_FILTER_USE_KEY);
}

/**
 * @} End of "addtogroup hooks".
 */
