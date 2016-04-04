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
  public function process() {
    // Processing of orphans happens in the background during cron runs.
    // @see \Drupal\og\Plugin\QueueWorker\DeleteOrphan
  }

}
