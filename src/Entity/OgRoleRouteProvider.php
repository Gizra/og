<?php

namespace Drupal\og\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for og_roles.
 */
class OgRoleRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getAddFormRoute($entity_type)) {
      $route->setOption('parameters', ['entity_type' => ['type' => 'entity:{entity_type}']])
        ->setOption('parameters', ['bundle' => ['type' => '{entity_type}:{bundle}']]);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getEditFormRoute($entity_type)) {
      $route->setDefault('_title_callback', '\Drupal\og_ui\Form\OgRoleForm::editRoleTitleCallback')
        ->setOption('parameters', ['group_type' => ['type' => 'entity:group_type']])
        ->setRequirement('_entity_access', 'og_role.update');
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeleteFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getDeleteFormRoute($entity_type)) {
      $route->setDefault('_title_callback', '\Drupal\og_ui\Form\OgRoleForm::editRoleTitleCallback');
      $route->setOption('parameters', ['group_type' => ['type' => 'entity:group_type']]);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCollectionRoute($entity_type)) {
      $route->setDefault('_title_callback', '\Drupal\og_ui\Controller\OgUiController::rolesOverviewPageTitleCallback')
        ->setOption('parameters', ['entity_type' => ['type' => 'entity:{entity_type}']])
        ->setRequirement('_permission', 'administer group');
      return $route;
    }
  }

}
