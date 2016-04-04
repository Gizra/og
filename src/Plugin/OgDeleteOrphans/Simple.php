<?php

namespace Drupal\og\Plugin\OgDeleteOrphans;

use Drupal\og\Og;
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
   * {@inheritdoc}
   */
  public function process() {
    while ($item = $this->queue->claimItem()) {
      $data = $item->data;
      $this->deleteOrphan($data['type'], $data['id']);
      $this->queue->deleteItem($item);
    }
  }

}
