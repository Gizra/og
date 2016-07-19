<?php

namespace Drupal\og_context\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OgContextConfigForm.
 *
 * @package Drupal\og_context\Form
 */
class OgContextConfigForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $og_context_config = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $og_context_config->label(),
      '#description' => $this->t("Label for the OG context config."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $og_context_config->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\og_context\Entity\OgContextConfig::load',
      ),
      '#disabled' => !$og_context_config->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $og_context_config = $this->entity;
    $status = $og_context_config->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label OG context config.', [
          '%label' => $og_context_config->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label OG context config.', [
          '%label' => $og_context_config->label(),
        ]));
    }
    $form_state->setRedirectUrl($og_context_config->urlInfo('collection'));
  }

}
