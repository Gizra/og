<?php

namespace Drupal\og_ui\Plugin\GroupAdminRoutes;
use Drupal\og_ui\OgUiAdminRouteAbstract;

/**
 * @GroupAdminRoutes(
 *   id = "people",
 *   path = "people"
 * )
 */
class People extends OgUiAdminRouteAbstract {

  /**
   * {@inheritdoc}
   */
  public function access() {
  }

  public function routes() {
    return [
      '/' => [$this, 'PeopleList'],
      '/add' => [$this, 'addPeopleForm'],
      '/delete' => [$this, 'deletePeopleForm'],
    ];
  }

  /**
   * Display list of people which belong to the group.
   */
  public function PeopleList() {

  }

  /**
   * Add people to the group.
   */
  public function addPeopleForm() {

  }

  /**
   * Delete members from group.
   */
  public function deletePeopleForm() {

  }

}
