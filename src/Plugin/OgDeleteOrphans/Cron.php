<?php

namespace Drupal\og\Plugin\OgDeleteOrphans;

use Drupal\og\OgDeleteOrphansBase;

/**
 * Performs a cron deletion of orphans.
 *
 * @OgDeleteOrphans(
 *  id = "cron",
 *  label = @Translation("Cron", context = "OgDeleteOrphans"),
 *  description = @Translation("The deletion is done in the background during cron. Best overall solution but requires cron to run regularly."),
 *  weight = 3
 * )
 */
class Cron extends OgDeleteOrphansBase {

  /**
   * {@inheritdoc}
   */
  public function process($entity_type, $entity_id) {
    // Orphans are processed one by one by the QueueWorker during cron runs
    // until the alotted time expires.
    // @see \Drupal\og\Plugin\QueueWorker\DeleteOrphan
    $this->deleteOrphan($entity_type, $entity_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueue() {
    // By design, every QueueWorker is executed on every cron run and will
    // start processing its designated queue. To make sure that our DeleteOrphan
    // queue worker will not start processing orphans that have been registered
    // by another plugin (e.g. the Batch plugin) we are using a dedicated queue.
    return $this->queueFactory->get('og_orphaned_group_content_cron', TRUE);
  }

}
