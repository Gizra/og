<?php

namespace Drupal\og\Plugin\OgDeleteOrphans;

use Drupal\Core\Entity\EntityInterface;
use Drupal\og\OgDeleteOrphansBase;

/**
 * Performs a simple deletion of orphans.
 *
 * @OgDeleteOrphans(
 *  id = "simple",
 *  label = @Translation("Simple", context = "OgDeleteOrphans"),
 *  description = @Translation("Immediately deletes the orphans when a group is deleted. Best suited for small sites with not a lot of group content."),
 *  weight = 0
 * )
 */
class Simple extends OgDeleteOrphansBase {

  /**
   * The parent entity which is about to be deleted.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $parent;

  /**
   * The orphans to delete.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $orphans;

  /**
   * {@inheritdoc}
   */
  public function register(EntityInterface $entity) {
    $this->parent = $entity;
    $this->orphans = $this->query();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->getGroupContent($this->parent);
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    throw new \Exception(__METHOD__ . ' is not implemented.');
  }

}
