<?php

namespace Drupal\og\Plugin\Action;

use Drupal\og\Entity\OgMembership;

/**
 * Removes a role from a group membership.
 *
 * @Action(
 *   id = "og_membership_remove_role_action",
 *   label = @Translation("Remove a role from the selected members"),
 *   type = "og_membership"
 * )
 */
class RemoveOgMembershipRole extends ChangeOgMembershipRoleBase {

  /**
   * {@inheritdoc}
   */
  public function execute(OgMembership $membership = NULL) {
    if (!$membership) {
      return;
    }
    $rid = $this->configuration['rid'];
    // Skip removing the role from the membership if it doesn't have it.
    if (in_array($rid, $membership->getRolesIds())) {
      $membership->revokeRoleById($rid)->save();
    }
  }

}
