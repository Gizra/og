<?php

namespace Drupal\og\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
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
 *   weight = 1,
 * )
 */
class OgSelection extends DefaultSelection {

  /**
   * Get the selection handler of the field.
   *
   * @return \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection
   *   Returns the selection handler.
   */
  public function getSelectionHandler() {
    $options = $this->getConfiguration();
    // The 'handler' key intentionally absent as we want the selection manager
    // to choose the best option.
    // @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager::getInstance()
    unset($options['handler']);
    // Remove also the backwards compatibility layer because that will be passed
    // to the chosen selection handler setter and, as an effect, will trigger a
    // deprecation notice.
    // @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase::resolveBackwardCompatibilityConfiguration()
    unset($options['handler_settings']);
    return \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
  }

  /**
   * Overrides ::buildEntityQuery.
   *
   * Return only group in the matching results.
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
    $definition = \Drupal::entityTypeManager()->getDefinition($target_type);

    if ($bundle_key = $definition->getKey('bundle')) {
      $bundles = Og::groupTypeManager()->getAllGroupBundles($target_type);

      if (!$bundles) {
        // If there are no bundles defined, we can return early.
        return $query;
      }
      $query->condition($bundle_key, $bundles, 'IN');
    }

    $user_groups = $this->getUserGroups();
    if (!$user_groups) {
      return $query;
    }

    $identifier_key = $definition->getKey('id');

    $ids = [];
    if (!empty($this->getConfiguration()['field_mode']) && $this->getConfiguration()['field_mode'] === 'admin') {
      // Don't include the groups, the user doesn't have create permission.
      foreach ($user_groups as $group) {
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
   * Return all the user's groups.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array with the user's group, or an empty array if none found.
   */
  protected function getUserGroups() {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    $other_groups = $membership_manager->getUserGroups($this->currentUser->id());
    return isset($other_groups[$this->configuration['target_type']]) ? $other_groups[$this->configuration['target_type']] : [];
  }

}
