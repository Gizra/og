<?php

namespace Drupal\og\Event;

/**
 * The group creation event interface.
 */
interface GroupCreationEventInterface {

  /**
   * The event name.
   */
  const EVENT_NAME = 'og.group_creation';

  /**
   * Returns the entity type ID of the group to which the permissions apply.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Returns the bundle ID of the group to which the permissions apply.
   *
   * @return string
   *   The bundle ID.
   */
  public function getBundleId();

}
