<?php

namespace Drupal\og\Plugin\Action;

use Drupal\og\Entity\OgMembership;

/**
 * Removes a role from a user.
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
  public function execute(OgMembership $member = NULL) {
    if (!$member) {
      return;
    }
    $rid = $this->configuration['rid'];
    $roles = $member->getRoles();
    $apply = FALSE;
    /** @var \Drupal\og\Entity\OgRole $role */
    foreach ($roles as $index => $role) {
      if ($rid == $role->id()) {
        unset($roles[$index]);
        $apply = TRUE;
      }
    }

    // Skip removing the role from the user if they already don't have it.
    // For efficiency manually save the original account before applying
    // any changes.
    if ($apply) {
      $member->original = clone $member;
      $member->setRoles($roles);
      $member->save();
    }
  }

}
