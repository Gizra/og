<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\EntityReferenceSelection\OgSelection.
 */

namespace Drupal\og\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;

/**
 * Provide default OG selection handler.
 *
 * @EntityReferenceSelection(
 *   id = "default:og",
 *   label = @Translation("OG selection"),
 *   entity_types = {"node"},
 *   group = "default",
 *   weight = 1
 * )
 */
class OgSelection extends DefaultSelection {

  /**
   * Get the current account.
   *
   * @return AccountInterface
   */
  public function getAccount() {
    if (empty($this->account)) {
      $this->setAccount(\Drupal::currentUser()->getAccount());
    }

    return $this->currentUser;
  }

  /**
   * Set the current object account.
   *
   * @param AccountInterface $account
   */
  public function setAccount(AccountInterface $account) {
    $this->currentUser = $account;
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
    $query = parent::buildEntityQuery($match, $match_operator);

    $target_type = $this->configuration['target_type'];

    $identifier_key = \Drupal::entityManager()->getDefinition($target_type)->getKey('id');
    $user_groups = $this->getUserGroups();
    $bundles = Og::groupManager()->getAllGroupBundles($target_type);

    $query->condition('type', $bundles, 'IN');

    if (!$user_groups) {
      return $query;
    }

    $ids = [];

    if ($this->configuration['handler_settings']['field_mode'] == 'admin') {
      // Don't include the groups, the user doesn't have create permission.
      foreach ($user_groups as $delta => $group) {
        if ($group->access('create')) {
          $ids[] = $group->id();
        }
      }

      if ($ids) {
        $query->condition($identifier_key, $ids, 'IN');
      }
    }
    else {
      // Determine which groups should be selectable.
      foreach ($user_groups as $group) {
        // Check if user has "create" permissions on those groups. If the user
        // doesn't have create permission, check if perhaps the content already
        // exists and the user has edit permission.
        if ($group->access('create')) {
          $ids[] = $group->id();
        }
      }
      if ($ids) {
        $query->condition($identifier_key, $ids, 'NOT IN');
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
   * Get the user's groups.
   *
   * @return ContentEntityInterface[]
   */
  private function getUserGroups() {
    $other_groups = Og::getEntityGroups('user', $this->getAccount()->id());
    return $other_groups[$this->configuration['target_type']];
  }

}
