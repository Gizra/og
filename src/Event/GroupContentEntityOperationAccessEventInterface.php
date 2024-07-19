<?php

declare(strict_types=1);

namespace Drupal\og\Event;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for events that provide access to group content entity operations.
 */
interface GroupContentEntityOperationAccessEventInterface extends AccessEventInterface {

  /**
   * The event name.
   */
  const EVENT_NAME = 'og.group_content_entity_operation_access';

  /**
   * Returns the entity operation being performed.
   *
   * @return string
   *   The entity operation, such as 'create', 'update' or 'delete'.
   */
  public function getOperation(): string;

  /**
   * Returns the group content entity upon which the operation is performed.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The group content entity.
   */
  public function getGroupContent(): ContentEntityInterface;

}
