<?php

namespace Drupal\og\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide OG User selection handler for memberships.
 *
 * @EntityReferenceSelection(
 *   id = "og:user",
 *   label = @Translation("OG Membership user selection"),
 *   group = "og",
 *   entity_types = {"user"},
 *   weight = 0
 * )
 */
class OgUserSelection extends DefaultSelection {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;


  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a new UserSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, Connection $connection, MembershipManagerInterface $membership_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);

    $this->connection = $connection;
    $this->userStorage = $entity_manager->getStorage('user');
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('database'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * Get the selection handler of the field.
   *
   * @return Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection
   *   Returns the selection handler.
   */
  public function getSelectionHandler() {
    $options = [
      'target_type' => 'user',
    ];
    return \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Anon can't be a group member.
    $query->condition('uid', 0, '<>');

    // The user entity doesn't have a label column.
    if (isset($match)) {
      $query->condition('name', $match, $match_operator);
    }

    // Adding the permission check is sadly insufficient for users: core
    // requires us to also know about the concept of 'blocked' and 'active'.
    if (!$this->currentUser->hasPermission('administer users')) {
      $query->condition('status', 1);
    }

    // @todo implement an easier, more consistent way to get the group type. At
    // the moment, this works either for checkboxes or OG Autocomplete widget
    // types on entities that have a getGroup() method. It also does not work
    // properly every time; for example during validation.
    $group = NULL;
    if (isset($this->configuration['entity'])) {
      $entity = $this->configuration['entity'];
      $group = is_callable([$entity, 'getGroup']) ? $entity->getGroup() : NULL;
    }

    if (isset($this->configuration['handler_settings']['group'])) {
      $group = $this->configuration['handler_settings']['group'];
    }

    if ($group === NULL) {
      return $query;
    }

    // @todo Excluding group members with a join would perform much better than
    // loading each membership associated with the group.
    $member_uids = [];
    /** @var \Drupal\og\Entity\OgMembership $membership */
    foreach ($this->membershipManager->getGroupMemberships($group) as $membership) {
      $member_uids[] = $membership->getUser()->id();
    }

    if (count($member_uids) > 0) {
      $query->condition('uid', $member_uids, 'NOT IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $user = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable user, it needs to be active.
    if (!$this->currentUser->hasPermission('administer users')) {
      /** @var \Drupal\user\UserInterface $user */
      $user->activate();
    }

    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (!$this->currentUser->hasPermission('administer users')) {
      $entities = array_filter($entities, function ($user) {
        /** @var \Drupal\user\UserInterface $user */
        return $user->isActive();
      });
    }
    return $entities;
  }

}
