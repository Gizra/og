<?php

declare(strict_types = 1);

namespace Drupal\og;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the user role entity type.
 *
 * @see \Drupal\og\Entity\OgRole
 */
class OgRoleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'delete') {
      [, , $id] = explode('-', $entity->id(), 3);
      $roles = [
        OgRoleInterface::ANONYMOUS,
        OgRoleInterface::AUTHENTICATED,
        OgRoleInterface::ADMINISTRATOR,
      ];

      if (in_array($id, $roles)) {
        return AccessResult::forbidden();
      }
    }

    // Group roles have no 'view' route, but can be used in views to show what
    // roles a member has. We therefore allow 'view' access so field formatters
    // such as entity_reference_label will work.
    if ($operation == 'view') {
      return AccessResult::allowed()->addCacheableDependency($entity);
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
