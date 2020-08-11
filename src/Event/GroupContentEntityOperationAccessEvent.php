<?php

declare(strict_types = 1);

namespace Drupal\og\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Event that determines access to group content entity operations.
 */
class GroupContentEntityOperationAccessEvent extends AccessEventBase implements GroupContentEntityOperationAccessEventInterface {

  /**
   * The entity operation being performed.
   *
   * @var string
   */
  protected $operation;

  /**
   * The group content entity upon which the operation is being performed.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $groupContent;

  /**
   * Constructs a GroupContentEntityOperationAccessEvent.
   *
   * @param string $operation
   *   The entity operation, such as "create", "update" or "delete".
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group in scope of which the access check is being performed.
   * @param \Drupal\Core\Entity\ContentEntityInterface $groupContent
   *   The group content upon which the entity operation is performed.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user for which to check access.
   */
  public function __construct(string $operation, ContentEntityInterface $group, ContentEntityInterface $groupContent, AccountInterface $user) {
    parent::__construct($group, $user);
    $this->operation = $operation;
    $this->groupContent = $groupContent;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperation(): string {
    return $this->operation;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupContent(): ContentEntityInterface {
    return $this->groupContent;
  }

}
