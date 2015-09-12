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
   * A static cache of entity type group mappings.
   *
   * @var array
   */
  protected static $groupEntityCache = [];

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
    $entity_type_id = $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();

    if (isset(static::$groupEntityCache[$entity_type_id][$entity_bundle])) {
      return static::$groupEntityCache[$entity_type_id][$entity_bundle];
    }

    if ($entity instanceof ContentEntityInterface) {
      $entity_type = $entity->getEntityType();
      $bundle_key = $entity_type->getKey('bundle');
      $bundle_entity = $entity->get($bundle_key)->entity;

      // @todo Or ...
//      $bundle_entity_type = $entity_type->getBundleEntityType();
//      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity */
//      $bundle_entity = \Drupal::entityManager()->getStorage($bundle_entity_type)->load($entity->bundle());

      $is_group = static::isGroupEntityType($bundle_entity);
      static::$groupEntityCache[$entity_type_id][$entity_bundle] = $is_group;

      return $is_group;
    }

    static::$groupEntityCache[$entity_type_id][$entity_bundle] = FALSE;
    return FALSE;
  }

  /**
   * Returns whether an entity type instance is a group type.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity_type
   *
   * @return bool
   */
  public static function isGroupEntityType(ConfigEntityInterface $entity_type) {
    return $entity_type->getThirdPartySetting('og', 'group', FALSE);
  }

  /**
   * Sets an entity type instance as being an OG group.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity_type
   */
  public static function addGroup(ConfigEntityInterface $entity_type) {
    // @todo handle other cases?
    $entity_type->setThirdPartySetting('og', 'group', TRUE);
  }

  /**
   * removes an entity type instance as being an OG group.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity_type
   */
  public static function removeGroup(ConfigEntityInterface $entity_type) {
    // @todo handle other cases?
    $entity_type->unsetThirdPartySetting('og', 'group');
  }

}
