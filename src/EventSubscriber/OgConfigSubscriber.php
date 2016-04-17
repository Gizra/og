<?php

/**
 * @file
 * Contains \Drupal\og\EventSubscriber\OgConfigSubscriber.
 */

namespace Drupal\og\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
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
    $field_storage = $event->getConfig();

    $entity_type = $raw_data['group_added']['entity_type_id'];
    $bundle = $raw_data['group_added']['bundle'];

    return;
    foreach (array_keys(og_get_group_audience_fields()) as $field_name) {
      $field = field_info_field($field_name);

      if ($field['settings']['target_type'] == $entity_type  && empty($field['settings']['handler_settings']['target_bundles'])) {
        return;
      }

      if ($field['settings']['target_type'] == $entity_type && in_array($bundle, $field['settings']['handler_settings']['target_bundles'])) {
        return;
      }
    }

    // If we reached here, it means we need to create a field.
    // Pick an unused name.
    $field_name = substr("og_user_$entity_type", 0, 32);
    $i = 1;
    while (field_info_field($field_name)) {
      $field_name = substr("og_user_$entity_type", 0, 32 - strlen($i)) . $i;
      ++$i;
    }

    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['field']['settings']['target_type'] = $entity_type;
    $og_field['instance']['label'] = t('Group membership');

    // If the user entity type has multiple bundles, make sure to attach a field
    // instance to all of them.
    $entity_info = entity_get_info('user');
    foreach (array_keys($entity_info['bundles']) as $user_bundle) {
      og_create_field($field_name, 'user', $user_bundle, $og_field);
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
