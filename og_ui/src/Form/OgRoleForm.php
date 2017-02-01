<?php


namespace Drupal\og_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\Entity\OgRole;

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
    $entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Role name'),
      '#default_value' => $entity->label(),
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 64,
      '#description' => $this->t('The name for this role. Example: "Moderator", "Editorial board", "Site architect".'),
    ];
    $form['name'] = array(
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
      '#size' => 30,
      '#maxlength' => 64,
      '#machine_name' => array(
        'exists' => ['\Drupal\og_uid\Entity\OgRole', 'load'],
      ),
    );
    $form['weight'] = array(
      '#type' => 'value',
      '#value' => $entity->getWeight(),
    );

    return parent::form($form, $form_state, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Prevent leading and trailing spaces in role names.
    $entity->set('label', trim($entity->label()));
    $entity->set('name', trim($entity->get('name')));
    $status = $entity->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('OG role %label has been updated.', ['%label' => $entity->label()]));
      $this->logger('user')->notice('OG role %label has been updated.', ['%label' => $entity->label(), 'link' => $edit_link]);
    }
    else {
      drupal_set_message($this->t('OG role %label has been added.', ['%label' => $entity->label()]));
      $this->logger('user')->notice('OG role %label has been added.', ['%label' => $entity->label(), 'link' => $edit_link]);
    }
    $form_state->setRedirect('og_ui.roles_overview', [
      'entity_type' => $entity->getGroupType(),
      'bundle' => $entity->getGroupBundle(),
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
