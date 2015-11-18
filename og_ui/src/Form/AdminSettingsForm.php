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
      '#title' => $this->t('Group manager full permissions'),
      '#description' => $this->t('When enabled the group manager will have all the permissions in the group.'),
      '#default_value' => $config_og->get('group_manager_full_access'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('og.settings')
      ->set('group_manager_full_access', $form_state->getValue('og_group_manager_full_access'))
      ->save();
  }

}
