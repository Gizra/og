<?php

namespace Drupal\og\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * The group creation event.
 */
class GroupCreationEvent extends Event implements GroupCreationEventInterface {

  /**
   * The entity type ID of the group type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle ID of the group type.
   *
   * @var string
   */
  protected $bundleId;

  /**
   * Constructs a GroupCreationEvent object.
   *
   * @param string $entity_type_id
   *   The entity type ID of the group type.
   * @param string $bundle_id
   *   The bundle ID of the group type.
   */
  public function __construct($entity_type_id, $bundle_id) {
    $this->entityTypeId = $entity_type_id;
    $this->bundleId = $bundle_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleId() {
    return $this->bundleId;
  }

}
