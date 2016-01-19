<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\EntityReferenceSelection\OgSelection.
 */

namespace Drupal\og\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\user\Entity\User;
use Drupal\og\Og;

/**
 * Provide default OG selection handler.
 *
 * Note that the id is correctly defined as "og:default" and not the other way
 * around, as seen in most other default selection handler (e.g. "default:node")
 * as OG's selection handler is a wrapper around those entity specific default
 * ones. That is, the same selection handler will be returned no matter what is
 * the target type of the reference field. Internally, it will call the original
 * selection handler, and use it for building the queries.
 *
 * @EntityReferenceSelection(
 *   id = "og:default",
 *   label = @Translation("OG selection"),
 *   group = "og",
 *   weight = 1
 * )
 */
class OgSelection extends DefaultSelection {

  /**
   * Get the selection handler of the field.
   *
   * @return DefaultSelection
   */
  public function getSelectionHandler() {
    $options = [
      'target_type' => $this->configuration['target_type'],
      // 'handler' key intentionally absent as we want the selection manager to
      // choose the best option.
      // @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager::getInstance()
      'handler_settings' => $this->configuration['handler_settings'],
    ];
    return \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
  }

  /**
   * Overrides the basic entity query object. Return only group in the matching
   * results.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {

    // Getting the original entity selection handler. OG selection handler using
    // the default selection handler of the entity, which the field reference
    // to, and add another logic to the query object i.e. check if the entities
    // bundle defined as group.
    
    $query = $this->getSelectionHandler()->buildEntityQuery($match, $match_operator);
    $target_type = $this->configuration['target_type'];
    $entityDefinition = \Drupal::entityTypeManager()->getDefinition($target_type);

    if ($bundle_key = $entityDefinition->getKey('bundle')) {
      $bundles = Og::groupManager()->getAllGroupBundles($target_type);
      $query->condition($bundle_key, $bundles, 'IN');
    }

    $user_groups = $this->getUserGroups();
    if (!$user_groups) {
      return $query;
    }
    
    $identifier_key = $entityDefinition->getKey('id');

    $ids = [];
    if ($this->configuration['handler_settings']['field_mode'] == 'admin') {
      // Don't include the groups, the user doesn't have create permission.
      foreach ($user_groups as $delta => $group) {
        $ids[] = $group->id();
      }

      if ($ids) {
        $query->condition($identifier_key, $ids, 'NOT IN');
      }
    }
    else {
      // Determine which groups should be selectable.
      foreach ($user_groups as $group) {
        $ids[] = $group->id();
      }
      if ($ids) {
        $query->condition($identifier_key, $ids, 'IN');
      }
      else {
        // User doesn't have permission to select any group so falsify this
        // query.
        $query->condition($identifier_key, -1, '=');
      }
    }

    return $query;
  }

  /**
   *
   * @return ContentEntityInterface[]
   */
  protected function getUserGroups() {
    $other_groups = Og::getEntityGroups(User::load($this->currentUser->id()));
    return isset($other_groups[$this->configuration['target_type']]) ? $other_groups[$this->configuration['target_type']] : [];
  }

}
