<?php

/**
 * @file
 * Contains \Drupal\og\OgGroupAudienceHelper.
 */

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * OG helper methods.
 */
class OgGroupAudienceHelper {

  /**
   * Return TRUE if a field can be used and has not reached maximum values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to check the field cardinality for.
   * @param string $field_name
   *   The field name to check the cardinality of.
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
