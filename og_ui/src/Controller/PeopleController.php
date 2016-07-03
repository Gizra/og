<?php

namespace Drupal\og_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\Dropbutton;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\Url;
use Drupal\og_ui\Form\OgUiAddPeopleForm;
use Drupal\og_ui\OgUi;
use Drupal\og_ui\OgUiAdminRouteInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\views\Views;

class PeopleController extends ControllerBase {

  public static function access() {
    /** @var OgUiAdminRouteInterface $plugin */
    $plugin_id = \Drupal::routeMatch()
      ->getRouteObject()
      ->getRequirement('_plugin_id');

    $plugin = OgUi::getGroupAdminPlugins()->createInstance($plugin_id);

    return $plugin->setGroup(OgUi::getEntity())->access();
  }

  /**
   * Display list of people which belong to the group.
   */
  public function PeopleList() {
    /** @var PrivateTempStoreFactory $session_storage */
    $session_storage = \Drupal::service('user.private_tempstore');
    $temp_storage = $session_storage->get('og_ui');
    $temp_storage->set('people_url', \Drupal::request()->getUri());

    $entity = OgUi::getEntity();
    $arguments = [$entity->getEntityTypeId(), $entity->id()];

    return Views::getView('group_members')->executeDisplay('default', $arguments);
  }

  /**
   * Add people to the group.
   */
  public function addPeopleForm() {
    return \Drupal::formBuilder()->getForm('\Drupal\og_ui\Form\OgUiAddPeopleForm');
  }

}
