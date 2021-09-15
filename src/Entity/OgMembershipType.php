<?php

declare(strict_types = 1);

namespace Drupal\og\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgMembershipTypeInterface;

/**
 * The membership type entity.
 *
 * A membership type is the bundle of the OG membership. There is a single
 * "default" bundle that comes out of the box, but others can be created. The
 * use case for membership types, is for example creating a "premium"
 * membership.
 *
 * By having a different membership type, also different fields can be attached,
 * so in our "premium" membership, we could add a date field, to indicate when
 * the subscription should be ended.
 *
 * @ConfigEntityType(
 *   id = "og_membership_type",
 *   label = @Translation("OG membership type"),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\og\Form\OgMembershipTypeForm",
 *       "edit" = "Drupal\og\Form\OgMembershipTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\og\OgMembershipTypeListBuilder"
 *   },
 *   admin_permission = "administer organic groups",
 *   config_prefix = "og_membership_type",
 *   bundle_of = "og_membership",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name"
 *   },
 *   config_export = {
 *     "type",
 *     "name",
 *     "description"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/membership-types/manage/{membership_type}",
 *     "delete-form" = "/admin/structure/membership-types/manage/{membership_type}/delete",
 *     "collection" = "/admin/structure/membership-types",
 *   }
 * )
 */
class OgMembershipType extends ConfigEntityBase implements OgMembershipTypeInterface {

  /**
   * The membership type.
   *
   * @var string
   */
  protected $type;

  /**
   * Return the ID of the entity.
   *
   * @return string|null
   *   The type of the entity.
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $status = parent::save();

    if ($this->isSyncing()) {
      // Do not create config while config import is in progress.
      return;
    }

    if ($status === SAVED_NEW) {
      FieldConfig::create([
        'field_name' => OgMembershipInterface::REQUEST_FIELD,
        'entity_type' => 'og_membership',
        'bundle' => $this->id(),
        'label' => 'Request Membership',
        'description' => 'Explain the motivation for your request to join this group.',
        'translatable' => TRUE,
        'settings' => [],
      ])->save();
    }

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if ($this->id() === OgMembershipInterface::TYPE_DEFAULT) {
      throw new \Exception('The default OG membership type cannot be deleted.');
    }
    parent::delete();
  }

}
