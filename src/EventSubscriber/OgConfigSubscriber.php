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

    // todo: implement logic.
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
