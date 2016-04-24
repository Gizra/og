<?php

/**
 * @file
 * Contains \Drupal\og\EventSubscriber\OgConfigSubscriber.
 */

namespace Drupal\og\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\field\Entity\FieldConfig;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OgConfigSubscriber implements EventSubscriberInterface {

  /**
   * Event subscribe handler; Acting upon adding a new group type.
   */
  public function configSave(ConfigCrudEvent $event) {
    $raw_data = $event->getConfig()->getRawData();

    if (empty($raw_data['group_added'])) {
      return;
    }

    // Check if we need to add a group audience on the user's entity.
    // We add a different field, so each field can be set differently.

    $entity_type = $raw_data['group_entity_id'];
    $bundle = $raw_data['group_bundle'];

    $fields = OgGroupAudienceHelper::getAllGroupAudienceFields('user', 'user');

    foreach ($fields as $field) {

      if ($field->getFieldStorageDefinition()->getSetting('target_type') == $entity_type) {

        if (!$field->getSetting('handler_settings')['target_bundles']) {
          return;
        }

        if (in_array($bundle, $field->getSetting('handler_settings')['target_bundles'])) {
          return;
        }
      }
    }

    // If we reached here, it means we need to create a field.
    // Pick an unused name.
    $field_name = substr("og_user_$entity_type", 0, 32);
    $i = 1;
    while (FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      $field_name = substr("og_user_$entity_type", 0, 32 - strlen($i)) . $i;
      ++$i;
    }

    if (!$user_bundles = \Drupal::entityTypeManager()->getDefinition('user')->getKey('bundle')) {
      $user_bundles = [];
    }

    $user_bundles[] = 'user';

    $settings = [
      'field_name' => $field_name,
      'field_storage_config' => [
        'settings' => [
          'target_type' => $entity_type,
        ],
      ],
      'field_config' => [
        'settings' => [
          'handler_settings' => [
            'target_bundles' => [$bundle => $bundle],
          ],
        ],
      ],
    ];

    foreach ($user_bundles as $user_bundle) {
      OG::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'user', $user_bundle, $settings);
    }
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events['config.save'][] = ['configSave'];
    return $events;
  }

}
