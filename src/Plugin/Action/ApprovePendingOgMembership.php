<?php

namespace Drupal\og\Plugin\Action;

use Drupal\og\OgMembershipInterface;

/**
 * Approves a pending group membership.
 *
 * @Action(
 *   id = "og_membership_approve_pending_action",
 *   label = @Translation("Approve the pending membership(s)"),
 *   type = "og_membership"
 * )
 */
class ApprovePendingOgMembership extends ChangeOgMembershipStateBase {

  /**
   * {@inheritdoc}
   */
  public function getTargetState() {
    return OgMembershipInterface::STATE_ACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalState() {
    return OgMembershipInterface::STATE_PENDING;
  }

}
