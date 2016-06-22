<?php

namespace Drupal\og_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\og_ui\OgUi;
use Drupal\og_ui\OgUiAdminRouteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PeopleController extends ControllerBase {


  public static function access() {
    /** @var OgUiAdminRouteInterface $plugin */
    $plugin = OgUi::getGroupAdminPlugins()->createInstance('people');

    return $plugin
      ->setGroup(OgUi::getEntity())
      ->access();
  }

  /**
   * Display list of people which belong to the group.
   */
  public function PeopleList() {
    return array(
      '#type' => 'item',
      '#markup' => 'people list',
    );
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

