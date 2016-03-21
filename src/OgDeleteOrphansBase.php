<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityInterface;

/**
 * Base implementation for OgDeleteOrphans plugins.
 */
abstract class OgDeleteOrphansBase implements OgDeleteOrphansInterface {

  /**
   * {@inheritdoc}
   */
  public function register(EntityInterface $entity) {
    throw new \Exception(__METHOD__ . ' is not implemented.');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    throw new \Exception(__METHOD__ . ' is not implemented.');
  }

  /**
   * Returns the group content associated with the given group entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of group content.
   */
  protected function getGroupContent(EntityInterface $entity) {
    return Og::getGroupContent($entity);
  }

}
