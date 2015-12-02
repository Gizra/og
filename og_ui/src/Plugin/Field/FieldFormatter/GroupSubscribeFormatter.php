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
use Drupal\og\OgGroupAudienceHelper;
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
    $account = \Drupal::currentUser()->getAccount();
    $field_name = $this->getSetting('field_name');

    // Entity is not a group.
    if (!Og::isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      return [];
    }

    if (($entity instanceof EntityOwnerInterface) && ($entity->getOwnerId() == $account->id())) {
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

    if (Og::isMember($entity, $account, [OG_STATE_ACTIVE, OG_STATE_PENDING])) {
      if (og_user_access($entity_type, $id, 'unsubscribe', $account)) {
        $links['title'] = $this->t('Unsubscribe from group');
        $links['href'] = "group/$entity_type/$id/unsubscribe";
        $links['class'] = ['group', 'unsubscribe'];
      }
    }
    else {
      if (Og::isMember($entity, $account, [OG_STATE_BLOCKED])) {
        // If user is blocked, they should not be able to apply for
        // membership.
        return [];
      }

      // Check if user can subscribe to the field.
      if (empty($field_name) && $audience_field_name = og_get_best_group_audience_field('user', $account, $entity_type, $bundle)) {
        $settings['field_name'] = $audience_field_name;
      }
      if (!$field_name) {
        return [];
      }

      // @todo Not sure this is applicable now we have our own dedicated field
      // type?
      // Check if entity is referencable.
      if ($this->getSetting('target_type') !== $entity->getEntityTypeId()) {
        // Group type doesn't match.
        return [];
      }

      // Check handler bundles, if any.
      $handler_settings = $this->getSetting('handler_settings');

      if (!empty($handler_settings['target_bundles']) && !in_array($bundle, $handler_settings['target_bundles'])) {
        // Bundles don't match.
        return [];
      }

      if (!OgGroupAudienceHelper::checkFieldCardinality($account, $field_name)) {
        $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

        $elements[0] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'title' => $this->formatPlural($cardinality, 'You are already registered to another group', 'You are already registered to @count groups'),
            'class' => ['group', 'other'],
          ],
          '#value' => $this->formatPlural($cardinality, 'You are already registered to another group', 'You are already registered to @count groups'),
        ];

        return $elements;
      }

      $url = "group/$entity_type/$id/subscribe";
      if (!empty($field_name)) {
        $url .= '/' . $field_name;
      }

      if (og_user_access($entity_type, $id, 'subscribe without approval', $account)) {
        $links['title'] = $this->t('Subscribe to group');
        $links['class'] = ['group', 'subscribe'];
        if ($account->isAuthenticated()) {
          $links['href'] = $url;
        }
        else {
          $links['href'] = 'user/login';
          $links['options'] = [
            'query' => [
              'destination' => $url],
          ];
        }
      }
      elseif (og_user_access($entity_type, $id, 'subscribe')) {
        $links['title'] = $this->t('Request group membership');
        $links['class'] = ['group', 'subscribe', 'request'];
        if ($account->isAuthenticated()) {
          $links['href'] = $url;
        }
        else {
          $links['href'] = 'user/login';
          $links['options'] = [
            'query' => [
              'destination' => $url],
          ];
        }
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

    if (!empty($links['title'])) {
      $links += [
        'options' => [
          'attributes' => [
            'title' => $links['title'],
            'class' => [$links['class']],
          ],
        ],
      ];

      $elements[0] = [
        '#type' => 'link',
        '#title' => $links['title'],
        '#href' => $links['href'],
        '#options' => $links['options'],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['field_name'] = 0;

    return $options;
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
      '#options' => $this->getAudienceFieldOptions(),
      '#default_value' => $this->getSetting('field_name'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $options = $this->getAudienceFieldOptions();

    $field_name = $this->getSetting('field_name');

    $summary[] = $this->t('Field: %label', array('%label' => $options[$field_name]));

    return $summary;
  }

  /**
   * Returns audience field options.
   *
   * @return array
   *   An array of audience field options.
   */
  protected function getAudienceFieldOptions() {
    $options = [0 => $this->t('Automatic (best matching)')];

    foreach (Og::getAllGroupAudienceFields('user', 'user') as $field_name => $field_definition) {
      $options[$field_name] = $field_definition->getLabel();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return AccessResult::allowed();
  }

}

