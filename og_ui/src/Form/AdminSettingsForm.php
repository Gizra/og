<?php

/**
 * @file
 * Contains \Drupal\og_ui\Form\AdminSettingsForm.
 */

namespace Drupal\og_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the main administration settings form for Organic groups.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_ui_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'og.settings',
      'og_ui.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config_og = $this->config('og.settings');
    $config_og_ui = $this->config('og_ui.settings');

    $form['og_group_manager_full_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Group manager has full permissions'),
      '#description' => $this->t('When enabled the group manager will have all the permissions in the group.'),
      '#default_value' => $config_og->get('group_manager_full_access'),
    ];

    $form['og_node_access_strict'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strict node access permissions'),
      '#description' => $this->t('When enabled Organic groups will restrict permissions for creating, updating and deleting according to the Organic groups access settings. Example: A content editor with the <em>Edit any page content</em> permission who is not a member of a group would be denied access to modifying page content in that group. (For restricting view access use the Organic groups access control module.)'),
      '#default_value' => $config_og->get('node_access_strict'),
    ];

    // @todo: Port og_ui_admin_people_view.

    $form['og_use_queue'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use queue'),
      '#description' => t("Use the core's queue process for operations such as deleting memberships when groups are deleted."),
      '#default_value' => $config_og->get('use_queue'),
    ];

    $form['og_orphans_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete orphans'),
      '#description' => $this->t('Delete orphaned group content (excluding users) when a group is deleted.'),
      '#default_value' => $config_og->get('orphans_delete'),
      '#states' => [
        'visible' => [
          ':input[name="og_use_queue"]' => ['checked' => TRUE],
        ],
      ],
      '#attributes' => ['class' => ['child-item']],
    ];

    $form['#attached']['library'][] = 'og_ui/form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('og.settings')
      ->set('group_manager_full_access', $form_state->getValue('og_group_manager_full_access'))
      ->set('node_access_strict', $form_state->getValue('og_node_access_strict'))
      ->set('use_queue', $form_state->getValue('og_use_queue'))
      ->set('orphans_delete', $form_state->getValue('og_orphans_delete'))
      ->save();
  }

}
