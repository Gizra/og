<?php

namespace Drupal\og\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provide default OG Role selection handler.
 *
 * @EntityReferenceSelection(
 *   id = "og:og_role",
 *   label = @Translation("OG Role selection"),
 *   group = "og",
 *   weight = 0
 * )
 */
class OgRoleSelection extends DefaultSelection {

  /**
   * Get the selection handler of the field.
   *
   * @return DefaultSelection
   *   Returns the selection handler.
   */
  public function getSelectionHandler() {
    $options = [
      'target_type' => 'og_role',
    ];
    return \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    $entity = $this->configuration['entity'];
    // The entity does not always exist, for example during validation.
    // @todo figure out how to always add the full conditions, especially if
    // this can be abused to reference incompatible roles.
    if (is_null($entity)) {
      return $query;
    }

    $group_type = $entity->getGroupEntityType();
    $group_bundle = $entity->getGroup()->bundle();
    $query->condition('group_type', $group_type, '=');
    $query->condition('group_bundle', $group_bundle, '=');
    $query->condition($query->orConditionGroup()
      ->condition('role_type', NULL, 'IS NULL')
      ->condition('role_type', 'required', '<>'));
    return $query;
  }

}
