<?php

namespace Drupal\og\Plugin\Action;

use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;

/**
 * Adds a role to a group membership.
 *
 * @Action(
 *   id = "og_membership_add_role_action",
 *   label = @Translation("Add a role to the selected members"),
 *   type = "og_membership"
 * )
 */
class AddOgMembershipRole extends ChangeOgMembershipRoleBase {

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
    // For efficiency manually save the original account before applying
    // any changes.
    $member->original = clone $member;
    $member->addRole($role);
    $member->save();
  }

}
