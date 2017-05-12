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
      $membership->getGroup()->bundle(),
      $role_name,
    ]);
    if (!in_array($role_id, $membership->getRolesIds())) {
      $membership->addRole(OgRole::load($role_id))->save();
    }
  }

}
