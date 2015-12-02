<?php

/**
 * @file
 * Contains \Drupal\og\OgHelper.
 */

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * OG helper methods.
 */
class OgHelper {

  /**
   * Return TRUE if a field can be used and has not reached maximum values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to check the field cardinality for.
   *
   * @return bool
   */
  public static function checkFieldCardinality(ContentEntityInterface $entity, $field_name) {
    $field_definition = $entity->getFieldDefinition($field_name);

    if (!$field_definition) {
      throw new FieldException(sprintf('No "%s" field found for %s %s entity', $field_name, $entity->bundle(), $entity->getEntityTypeId()));
    }

    $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();

    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      return TRUE;
    }

    return $entity->get($field_name)->count() < $cardinality;
  }

}
