<?php

namespace Drupal\og_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\og\Og;
use Drupal\og_ui\OgUi;
use Drupal\og_ui\OgUiAdminRouteInterface;
use Drupal\views\Entity\View;
use Drupal\views\Views;

class PeopleController extends ControllerBase {


  public static function access() {
    /** @var OgUiAdminRouteInterface $plugin */
    $plugin = OgUi::getGroupAdminPlugins()->createInstance(
      \Drupal::routeMatch()->getRouteObject()->getRequirement('_plugin_id')
    );

    return $plugin
      ->setGroup(OgUi::getEntity())
      ->access();
  }

  /**
   * Display list of people which belong to the group.
   */
  public function PeopleList() {
    $entity = OgUi::getEntity();
    $arguments = [$entity->getEntityTypeId(), $entity->id()];
    return Views::getView('group_members')->executeDisplay('default', $arguments);
  }

  /**
   * Add people to the group.
   */
  public function addPeopleForm() {
    return array(
      '#type' => 'item',
      '#markup' => 'form',
    );
  }

  /**
   * Delete members from group.
   */
  public function deletePeopleForm() {
    return array(
      '#type' => 'item',
      '#markup' => 'delete pople',
    );
  }
  
}

