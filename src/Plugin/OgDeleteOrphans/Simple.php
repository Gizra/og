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
      $entity = $this->entityTypeManager->getStorage($data['type'])->load($data['id']);
      // Only delete content that is fully orphaned, i.e. it is no longer
      // associated with any groups.
      $group_count = Og::getGroupCount($entity);
      if ($group_count == 0) {
        $entity->delete();
      }
      $this->queue->deleteItem($item);
    }
  }

}
