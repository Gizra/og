<?php

namespace Drupal\og\Plugin\Action;

use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;

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
    /** @var \Drupal\og\Entity\OgRole $role */
    foreach ($roles as $role) {
      if ($rid == $role->id()) {
        return;
      }
    }
    $role = OgRole::load($rid);

    if (!$role) {
      throw new \Exception('Unknown role ' . $rid);
    }
    // Skip removing the role from the user if they already don't have it.
    // For efficiency manually save the original account before applying
    // any changes.
    $member->original = clone $member;
    $member->removeRole($rid);
    $member->save();
  }

}
