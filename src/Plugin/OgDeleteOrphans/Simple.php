<?php

namespace Drupal\og\Plugin\OgDeleteOrphans;

use Drupal\Core\Entity\EntityInterface;
use Drupal\og\OgDeleteOrphansBase;

/**
 * Performs an on-the-fly deletion of orphans.
 *
 * @OgDeleteOrphans(
 *  id = "simple",
 *  label = @Translation("Simple", context = "OgDeleteOrphans"),
 *  description = @Translation("Immediately deletes the orphans when a group is deleted. Best suited for small sites with not a lot of group content."),
 * )
 */
class Simple extends OgDeleteOrphansBase {

  /**
   * {@inheritdoc}
   */
  public function register(EntityInterface $entity) {
    parent::register($entity);
    // Delete the orphans on the fly.
    drupal_register_shutdown_function([$this, 'process']);
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    $queue = $this->getQueue();
    while ($item = $queue->claimItem()) {
      $data = $item->data;
      $this->deleteOrphan($data['type'], $data['id']);
      $queue->deleteItem($item);
    }
  }

}
