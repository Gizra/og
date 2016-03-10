<?php

namespace Drupal\og\Plugin\OgDeleteOrphans;

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
    throw new \Exception(__METHOD__ . ' is not implemented.');
  }

}
