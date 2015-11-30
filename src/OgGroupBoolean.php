<?php

/**
 * @file
 * Contains \Drupal\og\OgGroupBoolean.
 */

namespace Drupal\og;

use Drupal\Core\TypedData\Plugin\DataType\BooleanData;

/**
 * Overridden boolean data type to hardcode TRUE.
 */
class OgGroupBoolean extends BooleanData {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->definition->getSetting('group_value');
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->getValue();
  }

}
