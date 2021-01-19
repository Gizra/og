<?php

declare(strict_types = 1);

namespace Drupal\og_test\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\og\Event\GroupContentEntityOperationAccessEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for testing Organic Groups.
 */
class OgTestEventSubscriber implements EventSubscriberInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs an OgTestEventSubscriber.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      GroupContentEntityOperationAccessEventInterface::EVENT_NAME => [['moderatorsCanManageComments']],
    ];
  }

  /**
   * Allows moderators to edit and delete comments in all groups.
   *
   * @param \Drupal\og\Event\GroupContentEntityOperationAccessEventInterface $event
   *   The event that fires when an entity operation is being performed on group
   *   content.
   */
  public function moderatorsCanManageComments(GroupContentEntityOperationAccessEventInterface $event): void {
    if ($this->state->get('og_test_group_content_entity_operation_access_alter', FALSE)) {
      // Moderators should have access to edit and delete all comments in all
      // groups.
      $is_comment = $event->getGroupContent()->getEntityTypeId() === 'comment';
      $user_can_moderate_comments = $event->getUser()->hasPermission('edit and delete comments in all groups');

      if ($is_comment && $user_can_moderate_comments) {
        $event->grantAccess();
      }
    }
  }

}
