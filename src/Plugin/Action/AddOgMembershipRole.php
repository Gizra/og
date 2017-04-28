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
  public function execute(OgMembership $membership = NULL) {
    if (!$membership) {
      return;
    }
    $rid = $this->configuration['rid'];
    if (!in_array($rid, $membership->getRolesIds())) {
      $membership->addRole(OgRole::load($rid))->save();
    }
  }

}
