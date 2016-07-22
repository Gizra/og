<?php

namespace Drupal\og\Plugin\OgContext;

use Drupal\og\OgContextBase;

/**
 * @OgContext(
 *  id = "url",
 *  label = "URL",
 *  description = @Translation("Get the group from the given URL.")
 * )
 */
class Url extends OgContextBase {

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
  }

}
