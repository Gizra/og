<?php

namespace Drupal\og\Plugin\Action;

/**
 * Removes multiple roles from a group membership.
 *
 * @Action(
 *   id = "og_membership_remove_multiple_roles_action",
 *   label = @Translation("Remove roles from the selected members"),
 *   type = "og_membership",
 *   confirm_form_route_name = "og.remove_multiple_roles_confirm"
 * )
 */
class RemoveMultipleOgMembershipRoles extends ChangeMultipleOgMembershipRolesBase {
}
