<?php

namespace Drupal\og\Plugin\Action;

use Drupal\og\OgMembershipInterface;

/**
 * Blocks a group membership.
 *
 * @Action(
 *   id = "og_membership_block_action",
 *   label = @Translation("Block the selected membership(s)"),
 *   type = "og_membership"
 * )
 */
class BlockOgMembership extends ChangeOgMembershipStateBase {

  /**
   * {@inheritdoc}
   */
  public function getTargetState() {
    return OgMembershipInterface::STATE_BLOCKED;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalState() {
    return NULL;
  }

}
