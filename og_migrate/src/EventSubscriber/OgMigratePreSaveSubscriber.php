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
    $plugin_id = $migration->getPluginId();

    $field_migrations = [
      'd7_field_instance',
      'd7_field_instance_per_view_mode',
      'd7_field_instance_per_form_display',
    ];

    if (in_array($plugin_id, $field_migrations)) {
      $row = $event->getRow();

      // The "og_membership_type_default" bundle is now "default".
      $old_default_bundle = 'og_membership_type_default';
      $new_default_bundle = 'default';

      $entity_type = $row->getDestinationProperty('entity_type');
      $bundle = $row->getDestinationProperty('bundle');

      if ($entity_type === 'og_membership' && $bundle === $old_default_bundle) {
        $row->setDestinationProperty('bundle', $new_default_bundle);
      }
    }
  }

}
