<?php

declare(strict_types = 1);

namespace Drupal\og\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;

/**
 * Provides an entity autocomplete with OG group context.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("og_autocomplete")
 */
class OgAutocomplete extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  public static function processEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Nothing to do if there is no target entity type.
    if (empty($element['#target_type'])) {
      throw new \InvalidArgumentException('Missing required #target_type parameter.');
    }

    // Provide default values and sanity checks for the #autocreate parameter.
    if ($element['#autocreate']) {
      if (!isset($element['#autocreate']['bundle'])) {
        throw new \InvalidArgumentException("Missing required #autocreate['bundle'] parameter.");
      }
      // Default the autocreate user ID to the current user.
      $element['#autocreate']['uid'] = isset($element['#autocreate']['uid']) ? $element['#autocreate']['uid'] : \Drupal::currentUser()->id();
    }

    // Store the selection settings in the key/value store and pass a hashed key
    // in the route parameters.
    $selection_settings = isset($element['#selection_settings']) ? $element['#selection_settings'] : [];
    $data = serialize($selection_settings) . $element['#target_type'] . $element['#selection_handler'];
    $selection_settings_key = Crypt::hmacBase64($data, \Drupal::service('private_key')->get() . Settings::getHashSalt());

    $key_value_storage = \Drupal::keyValue('entity_autocomplete');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    $element['#autocomplete_route_name'] = 'og.entity_autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'target_type' => $element['#target_type'],
      'selection_handler' => $element['#selection_handler'],
      'selection_settings_key' => $selection_settings_key,
      'entity_type_id' => $element['#og_group']->getEntityTypeId(),
      'group' => $element['#og_group']->id(),
    ];

    return $element;
  }

}
