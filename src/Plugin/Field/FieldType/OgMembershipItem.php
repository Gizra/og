<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgMembershipItem.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\StringTranslation\TranslationWrapper;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\og\Controller\OG;

/**
 * Class OgMembershipItem.
 *
 * @FieldType(
 *   id = "og_membership_reference",
 *   label = @Translation("OG membership reference"),
 *   description = @Translation("An entity field containing an OG membership reference."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_formatter = "og_complex",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   constraints = {"ValidReference" = {}}
 * )
 */
class OgMembershipItem extends FieldItemBase {

  /**
   * @var array
   */
  protected $groups = [];

  /**
   * @todo Remove when we have a widget, fielditem list implementation.
   *
   * @return bool
   *   TRUE if the item holds an unsaved entity.
   */
  public function hasNewEntity() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $target_type_info = \Drupal::entityManager()->getDefinition($settings['target_type']);

    $properties['target_id'] = DataDefinition::create('string')
      ->setLabel(new TranslationWrapper('@label ID', ['@label' => 'OG group']));

    $properties['entity'] = DataReferenceDefinition::create('entity')
      ->setLabel($target_type_info->getLabel())
      // The OG membership object is loaded based on the field config and the
      // parent.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create($settings['target_type']))
      ->addConstraint('EntityType', $settings['target_type']);

    if (isset($settings['target_bundle'])) {
      $properties['entity']->addConstraint('Bundle', $settings['target_bundle']);
      $properties['entity']->getTargetDefinition()
        ->addConstraint('Bundle', $settings['target_bundle']);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'target_type' => \Drupal::moduleHandler()->moduleExists('node') ? 'node' : 'user',
      'target_bundle' => NULL,
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'handler' => 'default:' . (\Drupal::moduleHandler()->moduleExists('node') ? 'node' : 'user'),
      'handler_settings' => array(),
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values) && !is_array($values)) {
      // If either a scalar or an object was passed as the value for the item,
      // assign it to the 'entity' property since that works for both cases.
      $this->set('entity', $values, $notify);
    }
    else {
      parent::setValue($values, FALSE);
      // Support setting the field item with only one property, but make sure
      // values stay in sync if only property is passed.
      if (isset($values['target_id']) && !isset($values['entity'])) {
        $this->onChange('target_id', FALSE);
      }
      elseif (!isset($values['target_id']) && isset($values['entity'])) {
        $this->onChange('entity', FALSE);
      }
      elseif (isset($values['target_id']) && isset($values['entity'])) {
        // If both properties are passed, verify the passed values match. The
        // only exception we allow is when we have a new entity: in this case
        // its actual id and target_id will be different, due to the new entity
        // marker.
        $entity_id = $this->get('entity')->getTargetIdentifier();
        if ($entity_id != $values['target_id']) {
          throw new \InvalidArgumentException('The target id and entity passed to the entity reference item do not match.');
        }
      }

      // Notify the parent if necessary.
      if ($notify && $this->parent) {
        $this->parent->onChange($this->getName());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Make sure that the target ID and the target property stay in sync.
    if ($property_name == 'entity') {
      $property = $this->get('entity');
      $target_id = $property->getTargetIdentifier();
      $this->writePropertyValue('target_id', $target_id);
    }
    elseif ($property_name == 'target_id') {
      $this->writePropertyValue('entity', $this->target_id);
    }

    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);

    /** @var \Drupal\Core\Entity\EntityInterface $parent */
    $parent_entity = $this->getEntity();
    $membership = OG::MembershipStorage()->create(OG::MembershipDefault());

    $membership->setFieldName($this->getName())
      ->setEntityType($parent_entity->getEntityTypeId())
      ->setEntityId($parent_entity->id())
      ->setGroupType($this->entity->getEntityTypeId())
      ->setGid($this->entity->id())
      ->save();
  }


}
