<?php

namespace Drupal\og\Plugin\OgDeleteOrphans;

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
  public function process() {
    $queue = $this->getQueue();
    while ($item = $queue->claimItem()) {
      $data = $item->data;
      $this->deleteOrphan($data['type'], $data['id']);
      $queue->deleteItem($item);
    }
  }

}
