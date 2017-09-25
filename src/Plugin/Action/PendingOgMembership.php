<?php

namespace Drupal\og\Plugin\Action;

use Drupal\og\OgMembershipInterface;

/**
 * Sets the pending state on a group membership.
 *
 * @Action(
 *   id = "og_membership_pending_action",
 *   label = @Translation("Set the selected membership(s) to pending state"),
 *   type = "og_membership"
 * )
 */
class PendingOgMembership extends ChangeOgMembershipStateBase {

  /**
   * {@inheritdoc}
   */
  public function getTargetState() {
    return OgMembershipInterface::STATE_PENDING;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalState() {
    return NULL;
  }

}
