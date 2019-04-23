<?php

namespace Drupal\og\Plugin\Action;

use Drupal\og\Entity\OgMembership;

/**
 * Removes a role from a group membership.
 *
 * @Action(
 *   id = "og_membership_remove_single_role_action",
 *   label = @Translation("Remove a role from the selected members"),
 *   type = "og_membership"
 * )
 */
class RemoveSingleOgMembershipRole extends ChangeSingleOgMembershipRoleBase {

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
    // Skip removing the role from the membership if it doesn't have it.
    if (in_array($role_id, $membership->getRolesIds())) {
      $membership->revokeRoleById($role_id)->save();
    }
  }

}
