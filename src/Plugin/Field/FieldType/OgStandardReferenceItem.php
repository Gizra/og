<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OgStandardReferenceItem.
 *
 * @FieldType(
 *   id = "og_standard_reference",
 *   label = @Translation("OG membership reference"),
 *   description = @Translation("An entity field containing an OG membership reference for user based entity."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_widget = "og_complex",
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   constraints = {"ValidOgMembershipReference" = {}}
 * )
 */
class OgStandardReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['access_override'] = FALSE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);

    // Field access settings.
    $form['access_override'] = [
      '#title' => $this->t('Allow entity access to control field access'),
      '#description' => $this->t('By default, the <em>administer group</em> permission is required to directly edit this field. Selecting this option will allow access to anybody with access to edit the entity.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('access_override'),
    ];

    return $form;
  }

}
