<?php

declare(strict_types = 1);

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
   * @return Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection
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

    // @todo implement an easier, more consistent way to get the group type. At
    // the moment, this works either for checkboxes or OG Autocomplete widget
    // types on entities that have a getGroup() method. It also does not work
    // properly every time; for example during validation.
    $group = NULL;
    if (isset($this->configuration['entity'])) {
      $entity = $this->configuration['entity'];
      $group = is_callable([$entity, 'getGroup']) ? $entity->getGroup() : NULL;
    }

    if (isset($this->configuration['handler_settings']['group'])) {
      $group = $this->configuration['handler_settings']['group'];
    }

    if ($group === NULL) {
      return $query;
    }

    $query->condition('group_type', $group->getEntityTypeId(), '=');
    $query->condition('group_bundle', $group->bundle(), '=');
    $query->condition($query->orConditionGroup()
      ->condition('role_type', NULL, 'IS NULL')
      ->condition('role_type', 'required', '<>'));
    return $query;
  }

}
