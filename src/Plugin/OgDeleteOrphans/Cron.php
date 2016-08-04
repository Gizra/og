<?php

namespace Drupal\og\Plugin\OgDeleteOrphans;

use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\og\OgDeleteOrphansBase;

/**
 * Performs a cron deletion of orphans.
 *
 * @OgDeleteOrphans(
 *  id = "cron",
 *  label = @Translation("Cron", context = "OgDeleteOrphans"),
 *  description = @Translation("The deletion is done in the background during cron. Best overall solution but requires cron to run regularly."),
 * )
 */
class Cron extends OgDeleteOrphansBase implements QueueWorkerInterface {

  /**
   * {@inheritdoc}
   */
  public function process() {
    // No online processing is done in this plugin. Instead, all orphans are
    // deleted during offline cron jobs by the DeleteOrphan queue worker.
    // @see \Drupal\og\Plugin\QueueWorker\DeleteOrphan
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Orphans are processed one by one by the QueueWorker during cron runs
    // until the alotted time expires.
    // @see \Drupal\og\Plugin\QueueWorker\DeleteOrphan
    $this->deleteOrphan($data['type'], $data['id']);
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
