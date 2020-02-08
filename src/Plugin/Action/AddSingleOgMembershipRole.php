<?php

namespace Drupal\og\Plugin\Action;

use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;

/**
 * Adds a role to a group membership.
 *
 * @Action(
 *   id = "og_membership_add_single_role_action",
 *   label = @Translation("Add a role to the selected members"),
 *   type = "og_membership"
 * )
 */
class AddSingleOgMembershipRole extends ChangeSingleOgMembershipRoleBase {

  /**
   * {@inheritdoc}
   */
  public function execute(OgMembership $membership = NULL) {
    if (!$membership) {
      return;
    }
    $role_name = $this->configuration['role_name'];
    $role_id = implode('-', [
      $membership->getGroupEntityType(),
      $membership->getGroupBundle(),
      $role_name,
    ]);
    // Only add the role if it is valid and doesn't exist yet.
    $role = OgRole::load($role_id);
    if ($membership->isRoleValid($role) && !$membership->hasRole($role_id)) {
      $membership->addRole($role)->save();
    }
  }

}
