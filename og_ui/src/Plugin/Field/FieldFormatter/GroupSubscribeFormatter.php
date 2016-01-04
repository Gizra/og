<?php

/**
 * @file
 * Contains \Drupal\og_ui\Plugin\Field\FieldFormatter\GroupSubscribeFormatter.
 */

namespace Drupal\og_ui\Plugin\Field\FieldFormatter;


use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\og\Og;
use Drupal\og\OgAccess;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\user\Entity\User;
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

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $entity = $items->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $bundle_id = $entity->bundle();

    $account = User::load(\Drupal::currentUser()->id());
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
      if (OgAccess::userAccess($entity, 'unsubscribe', $account)) {
        $link['title'] = $this->t('Unsubscribe from group');
        $link['url'] = Url::fromRoute('og_ui.unsubscribe', ['entity_type_id' => $entity_type_id, 'entity_id' => $entity->id()]);
        $link['class'] = ['unsubscribe'];
      }
    }
    else {
      if (Og::isMember($entity, $account, [OG_STATE_BLOCKED])) {
        // If user is blocked, they should not be able to apply for
        // membership.
        return [];
      }

      // Check if user can subscribe to the field.
      if (empty($field_name) && ($audience_field_name = OgGroupAudienceHelper::getMatchingField($account, $entity_type_id, $bundle_id))) {
        $field_name = $audience_field_name;
      }

      if (empty($field_name)) {
        return [];
      }

      // Check if entity is referencable.
      if ($this->getSetting('target_type') !== $entity->getEntityTypeId()) {
        // Group type doesn't match.
        return [];
      }

      // Check handler bundles, if any.
      $handler_settings = $this->getSetting('handler_settings');

      if (!empty($handler_settings['target_bundles']) && !in_array($bundle_id, $handler_settings['target_bundles'])) {
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

      // If hte user is authenticated, set up the subscribe link.
      if ($account->isAuthenticated()) {
        $parameters = [
          'entity_type_id' => $entity->getEntityTypeId(),
          'entity_id' => $entity->id(),
        ];

        // Add the field name as an additional query parameter.
        if (!empty($field_name)) {
          $parameters['field_name'] = $field_name;
        }

        $url = Url::fromRoute('og_ui.subscribe', $parameters);
      }
      // Otherwise, link to user login and redirect back to here.
      else {
        $url = Url::fromRoute('user.login', [], ['query' => $this->getDestinationArray()]);
      }

      if (OgAccess::userAccess($entity, 'subscribe without approval', $account)) {
        $link['title'] = $this->t('Subscribe to group');
        $link['class'] = ['subscribe'];
        $link['url'] = $url;
      }
      elseif (OgAccess::userAccess($entity, 'subscribe')) {
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
