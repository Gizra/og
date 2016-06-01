<?php

namespace Drupal\og_ui\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\Entity\OgRole;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OgRolesForm extends FormBase {
  
  public function __construct(EntityStorageInterface $og_role_storage) {
    $this->ogRoleStorage = $og_role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('og_role')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_ui_roles_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $bundle = NULL) {
    // Retrieve a list of all roles for this bundle.
    $roles = $this->ogRoleStorage->loadByProperties(['group_type' => $entity_type, 'group_bundle' => $bundle]);
    $args = func_get_args();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }
  
}
