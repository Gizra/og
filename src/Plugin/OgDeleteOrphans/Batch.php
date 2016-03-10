<?php

namespace Drupal\og\Plugin\OgDeleteOrphans;

use Drupal\og\OgDeleteOrphansBase;

/**
 * Performs a batch deletion of orphans.
 *
 * @OgDeleteOrphans(
 *  id = "batch",
 *  label = @Translation("Batch", context = "OgDeleteOrphans"),
 *  description = @Translation("The deletion is done in a batch operation. Good for large websites with a lot of content."),
 *  weight = 2
 * )
 */
class Batch extends OgDeleteOrphansBase {

  /**
   * {@inheritdoc}
   */
  public function process() {
    throw new \Exception(__METHOD__ . ' is not implemented.');
  }

}
