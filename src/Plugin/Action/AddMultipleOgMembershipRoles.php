<?php

namespace Drupal\og\Plugin\Action;

/**
 * Adds multiple roles to a group membership.
 *
 * @Action(
 *   id = "og_membership_add_multiple_roles_action",
 *   label = @Translation("Add roles to the selected members"),
 *   type = "og_membership",
 *   confirm_form_route_name = "og.add_multiple_roles_confirm"
 * )
 */
class AddMultipleOgMembershipRoles extends ChangeMultipleOgMembershipRolesBase {
}
