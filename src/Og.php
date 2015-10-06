<?php

/**
 * @file
 * Contains \Drupal\og\Og.
 */

namespace Drupal\og;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * A static helper class for OG.
 */
class Og {

  /**
   * Check if the given entity is a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   True or false if the given entity is group.
   */
  public static function isGroup(EntityInterface $entity) {
    return static::groupManager()->entityIsGroup($entity);
  }

  /**
   * Returns whether an entity bundle instance is a group type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity_type
   *
   * @return bool
   */
  public static function isGroupBundle(EntityInterface $entity) {
    return static::groupManager()->entityBundleIsGroup($entity);
  }

  /**
   * Sets an entity type instance as being an OG group.
   *
   * @param string $entity_type_id
   * @param string $bundle_id
   */
  public static function addGroup($entity_type_id, $bundle_id) {
    return static::groupManager()->addGroup($entity_type_id, $bundle_id);
  }

  /**
   * Removes an entity type instance as being an OG group.
   *
   * @param string $entity_type_id
   * @param string $bundle_id
   */
  public static function removeGroup($entity_type_id, $bundle_id) {
    return static::groupManager()->removeGroup($entity_type_id, $bundle_id);
  }

  /**
   * Returns the group manager instance.
   *
   * @return \Drupal\og\OgGroupManager
   */
  protected static function groupManager() {
    // @todo store static reference for this?
    return \Drupal::service('og.group.manager');
  }

}
