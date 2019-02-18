<?php

namespace Drupal\og_migrate\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to migrate.pre_save event.
 */
class OgMigratePreSaveSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [MigrateEvents::PRE_ROW_SAVE => ['onPreRowSave']];
  }

  /**
   * Alters organic groups entity reference fields with incompatible data.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The pre-row save event.
   */
  public function onPreRowSave(MigratePreRowSaveEvent $event) {
    $migration = $event->getMigration();
    if ($migration->getPluginId() === 'd7_field_instance') {
      $row = $event->getRow();
      if ($row->getDestinationProperty('type') === 'entity_reference') {
        // Cleans any bad data from field config before it's saved otherwise
        // the schema checker will barf.
        $settings = $row->getDestinationProperty('settings');

        if (isset($settings['handler_settings']['behaviors'])) {
          unset($settings['handler_settings']['behaviors']);
        }
        if (isset($settings['handler_settings']['membership_type'])) {
          unset($settings['handler_settings']['membership_type']);
          $settings['handler'] = 'og:default';

          $row->setDestinationProperty('type', 'og_standard_reference');
        }

        $row->setDestinationProperty('settings', $settings);
      }
    }
    elseif ($migration->getPluginId() === 'd7_field') {
      $field_names = ['og_group_ref', 'og_user_node'];
      $row = $event->getRow();
      if (in_array($row->getDestinationProperty('field_name'), $field_names)) {
        $row->setDestinationProperty('type', 'og_standard_reference');
      }
    }
  }

}
