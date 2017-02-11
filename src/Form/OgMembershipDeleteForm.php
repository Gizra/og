<?php

namespace Drupal\og\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a form for deleting a node.
 */
class OgMembershipDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $membership = $this->getEntity();

    return $this->t("%user has been unsubscribed from %group.", [
      '%user' => $membership->getUser()->getDisplayName(),
      '%group' => $membership->getGroup()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    /** @var \Drupal\og\Entity\OgMembership $entity */
    $membership = $this->getEntity();

    $this->logger('og')->notice("OG Membership: deleted the @membership_type membership for the user uid: @uid to the group of the entity-type @group_type and ID: @gid", [
      '@membership_type' => $membership->getType(),
      '@uid' => $membership->getUser()->id(),
      '@group_type' => $membership->getGroupEntityType(),
      '@gid' => $membership->getGroupId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\og\Entity\OgMembership $entity */
    $membership = $this->getEntity();

    return $this->t("Are you sure you want to unsubscribe %user from %group?", [
      '%user' => $membership->getUser()->getDisplayName(),
      '%group' => $membership->getGroup()->label(),
    ]);
  }

}
