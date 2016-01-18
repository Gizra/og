<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgMembershipReferenceItem.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OgMembershipReferenceItem.
 *
 * @FieldType(
 *   id = "og_membership_reference",
 *   label = @Translation("OG membership reference"),
 *   description = @Translation("An entity field containing an OG membership reference."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_widget = "og_complex",
 *   default_formatter = "og_complex",
 *   list_class = "\Drupal\og\Plugin\Field\FieldType\OgMembershipReferenceItemList",
 *   constraints = {"ValidOgMembershipReference" = {}}
 * )
 */
class OgMembershipReferenceItem extends EntityReferenceItem {

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

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    // @todo When the FieldStorageConfig::hasCustomStorage method can be changed
    // this will not be needed to prevent errors. Can just be an empty array,
    // similar to PathItem.
    return ['columns' => []];
  }

}
