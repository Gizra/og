<?php

namespace Drupal\og_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgRoleInterface;

/**
 * Form to add or edit an OG role.
 */
class OgRoleForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $og_role = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Role name'),
      '#default_value' => $og_role->label(),
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 64,
      '#description' => $this->t('The name for this role. Example: "Moderator", "Editorial board", "Site architect".'),
    ];

    $form['name'] = [
      '#type' => 'machine_name',
      '#default_value' => $og_role->getName(),
      '#required' => TRUE,
      '#disabled' => !$og_role->isNew(),
      '#size' => 30,
      '#maxlength' => 64,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#field_prefix' => $og_role->getGroupType() . '-' . $og_role->getGroupBundle() . '-',
    ];
    $form['weight'] = [
      '#type' => 'value',
      '#value' => $og_role->getWeight(),
    ];

    $form['role_type'] = [
      '#type' => 'value',
      '#value' => OgRoleInterface::ROLE_TYPE_STANDARD,
    ];

    return parent::form($form, $form_state, $og_role);
  }

  /**
   * Machine name callback.
   *
   * Cannot use OgRole::load as the #machine_name callback as we are only
   * allowing editing the role name.
   *
   * @param string $role_name
   *   The role name.
   *
   * @return OgRole|NULL
   *   The OG role if it exists. NULL otherwise.
   */
  public function exists($role_name) {
    $og_role = $this->entity;
    $role_id = implode('-', [
      $og_role->getGroupType(),
      $og_role->getGroupBundle(),
      $role_name,
    ]);

    return OgRole::load($role_id);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $og_role = $this->entity;

    // Prevent leading and trailing spaces in role names.
    $og_role->set('label', trim($og_role->label()));
    $og_role->set('name', trim($og_role->get('name')));
    $status = $og_role->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('OG role %label has been updated.', ['%label' => $og_role->label()]));
      $this->logger('user')->notice('OG role %label has been updated.', ['%label' => $og_role->label(), 'link' => $edit_link]);
    }
    else {
      drupal_set_message($this->t('OG role %label has been added.', ['%label' => $og_role->label()]));
      $this->logger('user')->notice('OG role %label has been added.', ['%label' => $og_role->label(), 'link' => $edit_link]);
    }
    $form_state->setRedirect('og_ui.roles_overview', [
      'entity_type' => $og_role->getGroupType(),
      'bundle' => $og_role->getGroupBundle(),
    ]);
  }

  /**
   * Title callback for the edit form for an OG role.
   *
   * @param \Drupal\og\Entity\OgRole $og_role
   *   The OG role being edited.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   An object that, when cast to a string, returns the translated title
   *   callback.
   */
  public function editRoleTitleCallback(OgRole $og_role) {
    return $this->t('Edit OG role %label', [
      '%label' => $og_role->getLabel(),
    ]);
  }

  /**
   * The role creation page title callback.
   *
   * @param string $bundle
   *   Entity type bundle.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   An object that, when cast to a string, returns the translated title
   *   callback.
   */
  public function addRoleTitleCallback($bundle) {
    return $this->t('Create %bundle OG role', [
      '%bundle' => $bundle,
    ]);
  }

}
