<?php

declare(strict_types = 1);

namespace Drupal\og;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the OG membership implementation for entity access control handler.
 */
class OgMembershipAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccess
   */
  protected $ogAccess;

  /**
   * The OG Membership Manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a OgMembershipAccessControllHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG Membership Manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, OgAccessInterface $og_access, MembershipManagerInterface $membership_manager) {
    parent::__construct($entity_type);
    $this->ogAccess = $og_access;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('og.access'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $membership, $operation, AccountInterface $account) {
    $group = $membership->getGroup();

    // Do not allow deleting the group owner's membership.
    if ($operation === 'delete' && ($group instanceof EntityOwnerInterface) && ($group->getOwnerId() == $membership->getOwner()->id())) {
      return AccessResult::forbidden();
    }

    // Ensure that there's at least one active membership in the group.
    if ($operation === 'delete' && $this->membershipManager->getGroupMembershipCount($group) === 1) {
      return AccessResult::forbidden();
    }

    // If the user has permission to administer all groups, allow access.
    if ($account->hasPermission('administer organic groups')) {
      return AccessResult::allowed();
    }

    $permissions = [OgAccess::ADMINISTER_GROUP_PERMISSION, 'manage members'];
    foreach ($permissions as $permission) {
      $result = $this->ogAccess->userAccess($group, $permission, $account);
      if ($result->isAllowed()) {
        return $result;
      }
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $context += [
      'entity_type_id' => $this->entityTypeId,
      'langcode' => LanguageInterface::LANGCODE_DEFAULT,
    ];

    $cid = 'create:' . $context['group']->getEntityTypeId() . ':' . $context['group']->id();
    if ($entity_bundle) {
      $cid .= ':' . $entity_bundle;
    }

    if (($access = $this->getCache($cid, 'create', $context['langcode'], $account)) !== NULL) {
      // Cache hit, no work necessary.
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Invoke hook_entity_create_access() and hook_ENTITY_TYPE_create_access().
    // Hook results take precedence over overridden implementations of
    // EntityAccessControlHandler::checkCreateAccess(). Entities that have
    // checks that need to be done before the hook is invoked should do so by
    // overriding this method.
    // We grant access to the entity if both of these conditions are met:
    // - No modules say to deny access.
    // - At least one module says to grant access.
    $args = [$account, $context, $entity_bundle];
    $access = array_merge(
      $this->moduleHandler()->invokeAll('entity_create_access', $args),
      $this->moduleHandler()->invokeAll($this->entityTypeId . '_create_access', $args)
    );

    $return = $this->processAccessHookResults($access);

    // Also execute the default access check except when the access result is
    // already forbidden, as in that case, it can not be anything else.
    if (!$return->isForbidden()) {
      $return = $return->orIf($this->checkCreateAccess($account, $context, $entity_bundle));
    }
    $result = $this->setCache($return, $cid, 'create', $context['langcode'], $account);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // If the user has permission to administer all groups, allow access.
    if ($account->hasPermission('administer organic groups')) {
      return AccessResult::allowed();
    }

    $group = $context['group'];

    // If we don't have a group, we can't really determine access other than
    // checking global account permissions.
    if ($group === NULL) {
      return AccessResult::neutral();
    }

    $permissions = [
      OgAccess::ADMINISTER_GROUP_PERMISSION,
      'add user',
      'manage members',
    ];
    foreach ($permissions as $permission) {
      $result = $this->ogAccess->userAccess($group, $permission, $account);
      if ($result->isAllowed()) {
        return $result;
      }
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $items ? $items->getEntity() : NULL;

    $administrative_fields = ['uid', 'state', 'roles'];
    if ($operation === 'edit' && in_array($field_definition->getName(), $administrative_fields, TRUE)) {
      $access = AccessResult::allowedIfHasPermission($account, 'administer organic groups')->addCacheableDependency($membership);
      if (!$membership || $access->isAllowed()) {
        return $access;
      }
      $permissions = [
        OgAccess::ADMINISTER_GROUP_PERMISSION,
        'manage members',
      ];
      $group = $membership->getGroup();
      foreach ($permissions as $permission) {
        $result = $this->ogAccess->userAccess($group, $permission, $account);
        if ($result instanceof RefinableCacheableDependencyInterface) {
          $result->addCacheableDependency($membership);
        }
        if ($result->isAllowed()) {
          return $result;
        }
      }
      // Return the last result, which must be denied.
      return $result;
    }

    if ($membership && $membership->isActive() && $field_definition->getName() === OgMembershipInterface::REQUEST_FIELD) {
      return AccessResult::forbidden()->addCacheableDependency($membership);
    }
    return AccessResult::allowed()->addCacheableDependency($membership);
  }

}
