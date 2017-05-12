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

    // Check if our cleanup process is already registered, so we don't add any
    // duplicates.
    $callbacks = array_filter(drupal_register_shutdown_function(), function ($callback) {
      $callable = $callback['callback'];
      return is_array($callable) && $callable[0] instanceof $this && $callable[1] === 'process';
    });

    // Register a shutdown function that deletes the orphans on the fly.
    if (empty($callbacks)) {
      drupal_register_shutdown_function([$this, 'process']);
    }
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
