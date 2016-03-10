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
    throw new \Exception(__METHOD__ . ' is not implemented.');
  }

}
