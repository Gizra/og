<?php

namespace Drupal\og\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;

/**
 * Plugin implementation for the OG subscribe formatter.
 *
 * @FieldFormatter(
 *   id = "og_group_subscribe",
 *   label = @Translation("OG Group subscribe"),
 *   description = @Translation("Display OG Group subscribe and un-subscribe links."),
 *   field_types = {
 *     "og_group"
 *   }
 * )
 */
class GroupSubscribeFormatter extends FormatterBase {

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // We cannot use the field cache, as the formatter changes according to the
    // user. Furthermore, this is not an expensive check, so we remove the cache
    // entirely.
    $elements['#cache']['max-age'] = 0;

    $group = $items->getEntity();
    $entity_type_id = $group->getEntityTypeId();

    $user = User::load(\Drupal::currentUser()->id());
    if (($group instanceof EntityOwnerInterface) && ($group->getOwnerId() == $user->id())) {
      // User is the group manager.
      $elements[0] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'title' => $this->t('You are the group manager'),
          'class' => ['group', 'manager'],
        ],
        '#value' => $this->t('You are the group manager'),
      ];

      return $elements;
    }

    /** @var \Drupal\og\OgAccessInterface $og_access */
    $og_access = \Drupal::service('og.access');

    if (Og::isMember($group, $user, [OgMembershipInterface::STATE_ACTIVE, OgMembershipInterface::STATE_PENDING])) {
      if ($og_access->userAccess($group, 'unsubscribe', $user)) {
        $link['title'] = $this->t('Unsubscribe from group');
        $link['url'] = Url::fromRoute('og.unsubscribe', ['entity_type_id' => $entity_type_id, 'entity_id' => $group->id()]);
        $link['class'] = ['unsubscribe'];
      }
    }
    else {
      if (Og::isMemberBlocked($group, $user)) {
        // If user is blocked, they should not be able to apply for
        // membership.
        return [];
      }

      // If the user is authenticated, set up the subscribe link.
      if ($user->isAuthenticated()) {
        $parameters = [
          'entity_type_id' => $group->getEntityTypeId(),
          'entity_id' => $group->id(),
        ];

        $url = Url::fromRoute('og.subscribe', $parameters);
      }
      else {
        // User is anonymous, link to user login and redirect back to here.
        $url = Url::fromRoute('user.login', [], ['query' => $this->getDestinationArray()]);
      }

      /** @var \Drupal\Core\Access\AccessResult $access */
      if (($access = $og_access->userAccess($group, 'subscribe without approval', $user)) && $access->isAllowed()) {
        $link['title'] = $this->t('Subscribe to group');
        $link['class'] = ['subscribe'];
        $link['url'] = $url;
      }
      elseif (($access = $og_access->userAccess($group, 'subscribe')) && $access->isAllowed()) {
        $link['title'] = $this->t('Request group membership');
        $link['class'] = ['subscribe', 'request'];
        $link['url'] = $url;
      }
      else {
        $elements[0] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'title' => $this->t('This is a closed group. Only a group administrator can add you.'),
            'class' => ['group', 'closed'],
          ],
          '#value' => $this->t('This is a closed group. Only a group administrator can add you.'),
        ];

        return $elements;
      }
    }

    if (!empty($link['title'])) {
      $link += [
        'options' => [
          'attributes' => [
            'title' => $link['title'],
            'class' => ['group'] + $link['class'],
          ],
        ],
      ];

      $elements[0] = [
        '#type' => 'link',
        '#title' => $link['title'],
        '#url' => $link['url'],
      ];
    }

    return $elements;
  }

}
