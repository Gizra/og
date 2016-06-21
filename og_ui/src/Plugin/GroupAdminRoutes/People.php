<?php

namespace Drupal\og_ui\Plugin\GroupAdminRoutes;
use Drupal\og_ui\OgUiAdminRouteAbstract;
use Drupal\og_ui\OgUiAdminRouteInterface;

/**
 * @GroupAdminRoutes(
 *   id = "people",
 *   path = "people",
 *   title = @Translation("People"),
 *   description = @Translation("Manage the group's members")
 * )
 */
class People extends OgUiAdminRouteAbstract {

  /**
   * {@inheritdoc}
   */
  public function access() {
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes() {
    return [
      OgUiAdminRouteInterface::MAIN => [
        'callback' => [$this, 'PeopleList'],
        'sub_path' => '',
      ],
      'add' => [
        'callback' => [$this, 'addPeopleForm'],
        'sub_path' => 'add',
      ],
      'delete' => [
        'callback' => [$this, 'deletePeopleForm'],
        'sub_path' => 'delete',
      ],
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
