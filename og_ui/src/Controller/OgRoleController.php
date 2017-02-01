<?php

namespace Drupal\og_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Controller for the og_role entity.
 *
 * @see \Drupal\og\Entity\OgRole.
 */
class OgRoleController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Form constructor for OgRole add form.
   *
   * @param string $entity_type
   *   Group type.
   * @param string $bundle
   *   Group bundle.
   *
   * @return array
   *   An associative array containing:
   *   - og_role_form: The og_role form as a renderable array.
   */
  public function addGroupTypeRole($entity_type, $bundle) {
    $build['#title'] = $this->t('Create New Role');

    // Show the actual reply box.
    $og_role = $this->entityManager()->getStorage('og_role')->create(array(
      'group_type' => $entity_type,
      'group_bundle' => $bundle,
    ));
    $build['og_role_form'] = $this->entityFormBuilder()->getForm($og_role);

    return $build;
  }

  public function addGroupRole(Entity $entity) {

  }

}