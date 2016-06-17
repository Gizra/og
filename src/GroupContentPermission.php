<?php

namespace Drupal\og;

/**
 * A group level permission.
 *
 * This is used for permissions that apply to the group as a whole, such as
 * 'subscribe without approval' and 'administer group'.
 */
class GroupContentPermission extends Permission {

  /**
   * The group content entity type ID to which this permission applies.
   *
   * @var $string
   */
  protected $entityType;

  /**
   * The group content bundle ID to which this permission applies.
   *
   * @var $string
   */
  protected $bundle;

  /**
   * The entity operation to which this permission applies.
   *
   * Examples: 'create', 'update', 'delete'.
   *
   * @var $string
   */
  protected $operation;

  /**
   * If this applies to all entities, or only to the ones owned by the user.
   *
   * Use this to make the distinction between 'edit any article content' and
   * 'edit own article content'.
   *
   * @var string
   *   Either 'any' or 'own'.
   */
  protected $ownership = 'any';

  /**
   * Returns the group content entity type ID to which this permission applies.
   *
   * @return string
   *   The group content entity type ID.
   */
  public function getEntityType() {
    return $this->get('entity type');
  }

  /**
   * Sets the group content entity type ID to which this permission applies.
   *
   * @param string $entity_type
   *   The group content entity type ID.
   *
   * @return $this
   */
  public function setEntityType($entity_type) {
    $this->set('entity type' , $entity_type);
    return $this;
  }

  /**
   * Returns the group content bundle ID to which this permission applies.
   *
   * @return string
   *   The group content bundle ID.
   */
  public function getBundle() {
    return $this->get('bundle');
  }

  /**
   * Sets the group content bundle ID to which this permission applies.
   *
   * @param string $bundle
   *   The group content bundle ID.
   *
   * @return $this
   */
  public function setBundle($bundle) {
    $this->set('bundle' , $bundle);
    return $this;
  }

  /**
   * Returns the operation to which this permission applies.
   *
   * @return string
   *   The operation.
   */
  public function getOperation() {
    return $this->get('operation');
  }

  /**
   * Sets the operation to which this permission applies.
   *
   * @param string $operation
   *   The operation. For example 'create', 'update', or 'delete'.
   *
   * @return $this
   */
  public function setOperation($operation) {
    $this->set('operation' , $operation);
    return $this;
  }

  /**
   * Returns the ownership scope of this permission.
   *
   * @return string
   *   The ownership scope, either 'any' or 'own'.
   */
  public function getOwnership() {
    return $this->get('ownership');
  }

  /**
   * Sets the ownership scope of this permission.
   *
   * @param string $ownership
   *   The ownership scope, either 'any' or 'own'.
   *
   * @return $this
   */
  public function setOwnership($ownership) {
    $this->set('ownership' , $ownership);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($property, $value) {
    parent::validate($property, $value);

    if ($property === 'ownership' && !in_array($value, ['any', 'own'])) {
      throw new \InvalidArgumentException('The ownership should be either "any" or "own".');
    }
  }

}
