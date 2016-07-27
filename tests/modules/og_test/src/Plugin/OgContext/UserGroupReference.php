<?php

namespace Drupal\og_test\Plugin\OgContext;

/**
 * @file
 * Contains \Drupal\og_test\Plugin\OgContext\UserGroupReference.
 */

use Drupal\og\OgContextBase;

/**
 * Get the group from a field reference attached to the current user.
 *
 * @OgContext(
 *  id = "user_group_reference",
 *  label = "User group reference",
 *  description = @Translation("A dummy plugin to return the first group from a field attach to the user.")
 * )
 */
class UserGroupReference extends OgContextBase {

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
  }

}
