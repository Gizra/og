<?php

/**
 * @file
 * Contains \Drupal\og_ui\Plugin\Field\FieldFormatter\GroupSubscribeFormatter.
 */

namespace Drupal\og_ui\Plugin\Field\FieldFormatter;


use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\Og;
use Drupal\user\EntityOwnerInterface;

/**
 * Plugin implementation for the OG subscribe formatter.
 *
 * @FieldFormatter(
 *   id = "og_ui_group_subscribe",
 *   label = @Translation("OG Group subscribe"),
 *   description = @Translation("Display OG Group subscribe links."),
 *   field_types = {
 *     "og_group"
 *   }
 * )
 */
class GroupSubscribeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $entity = $items->getEntity();
    // @todo Inject this properly.
    $account = \Drupal::currentUser()->getAccount();

    if (!Og::isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      return [];
    }

    if (($entity instanceof EntityOwnerInterface) && ($entity->getOwnerId() == $account->id())) {
      // User is the group manager.
      $elements[0] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['title' => t('You are the group manager'), 'class' => 'group manager'],
        '#value' => t('You are the group manager'),
      ];

      return $elements;
    }

    if (og_is_member($entity_type, $id, 'user', $account, [OG_STATE_ACTIVE, OG_STATE_PENDING])) {
      if (og_user_access($entity_type, $id, 'unsubscribe', $account)) {
        $links['title'] = $this->t('Unsubscribe from group');
        $links['href'] = "group/$entity_type/$id/unsubscribe";
        $links['class'] = 'group unsubscribe';
      }
    }
    else {
      if (og_is_member($entity_type, $id, 'user', $account, array(OG_STATE_BLOCKED))) {
        // If user is blocked, they should not be able to apply for
        // membership.
        return [];
      }

      // Check if user can subscribe to the field.
      if (empty($settings['field_name']) && $audience_field_name = og_get_best_group_audience_field('user', $account, $entity_type, $bundle)) {
        $settings['field_name'] = $audience_field_name;
      }
      if (!$settings['field_name']) {
        return [];
      }

      $field_info = field_info_field($settings['field_name']);

      // Check if entity is referencable.
      if ($field_info['settings']['target_type'] != $entity_type) {
        // Group type doesn't match.
        return [];
      }
      if (!empty($field_info['settings']['handler_settings']['target_bundles']) && !in_array($bundle, $field_info['settings']['handler_settings']['target_bundles'])) {
        // Bundles don't match.
        return [];
      }

      if (!og_check_field_cardinality('user', $account, $settings['field_name'])) {
        $elements[0] = array(
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => array('title' => format_plural($field_info['cardinality'], 'You are already registered to another group', 'You are already registered to @count groups'), 'class' => 'group other'),
          '#value' => format_plural($field_info['cardinality'], 'You are already registered to another group', 'You are already registered to @count groups'),
        );

        return $elements;
      }

      $url = "group/$entity_type/$id/subscribe";
      if ($settings['field_name']) {
        $url .= '/' . $settings['field_name'];
      }

      if (og_user_access($entity_type, $id, 'subscribe without approval', $account)) {
        $links['title'] = t('Subscribe to group');
        $links['class'] = 'group subscribe';
        if ($account->uid) {
          $links['href'] = $url;
        }
        else {
          $links['href'] = 'user/login';
          $links['options'] = array('query' => array('destination' => $url));
        }
      }
      elseif (og_user_access($entity_type, $id, 'subscribe')) {
        $links['title'] = t('Request group membership');
        $links['class'] = 'group subscribe request';
        if ($account->uid) {
          $links['href'] = $url;
        }
        else {
          $links['href'] = 'user/login';
          $links['options'] = array('query' => array('destination' => $url));
        }
      }
      else {
        $elements[0] = array(
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => array('title' => t('This is a closed group. Only a group administrator can add you.'), 'class' => 'group closed'),
          '#value' => t('This is a closed group. Only a group administrator can add you.'),
        );

        return $elements;
      }
    }

    if (!empty($links['title'])) {
      $links += array('options' => array('attributes' => array('title' => $links['title'], 'class' => array($links['class']))));
      $elements[0] = array(
        '#type' => 'link',
        '#title' => $links['title'],
        '#href' => $links['href'],
        '#options' => $links['options'],
      );
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['field_name'] = [
      '#title' => $this->t('Field name'),
      '#description' => $this->t('Select the field that should register the user subscription.'),
      '#type' => 'select',
      '#options' => [0 => $this->t('Automatic (best matching)')], //+ og_get_group_audience_fields('user', 'user'),
      '#default_value' => $this->getSetting('field_name'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($field_name = $this->getSetting('field_name')) {
      $fields = og_get_group_audience_fields();
      $summary[] = $this->t('Field %label', array('%label' => $fields[$field_name]));
    }
    else {
      $summary[] = $this->t('No field selected (best matching)');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    // Always allow an entity author's username to be read, even if the current
    // user does not have permission to view the entity author's profile.
    return AccessResult::allowed();
  }

}

