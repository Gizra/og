<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\OgAccess;
use Drupal\og\PermissionManager;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\RoleInterface;
use Prophecy\Argument;

/**
 * Base class for tests of the OgAccess class.
 */
class OgAccessTestBase extends UnitTestCase {

  /**
   * The mocked config handler.
   *
   * @var \Drupal\Core\Config\Config|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $config;

  /**
   * A mocked test user.
   *
   * @var \Drupal\user\UserInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $user;

  /**
   * The ID of the test group.
   *
   * @var string
   */
  protected $groupId;

  /**
   * The entity type ID of the test group.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle ID of the test group.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The mocked test group.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $group;

  /**
   * The mocked group manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupTypeManager;

  /**
   * The mocked permission manager.
   *
   * @var \Drupal\og\PermissionManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $permissionManager;

  /**
   * The OgAccess class, this is the system under test.
   *
   * @var \Drupal\og\OgAccessInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogAccess;

  /**
   * The group membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $membershipManager;

  /**
   * The OG group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface
   */
  protected $groupAudienceHelper;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The membership entity.
   *
   * @var \Drupal\og\OgMembershipInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $membership;

  /**
   * The OG role.
   *
   * @var \Drupal\og\OgRoleInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogRole;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->groupId = $this->randomMachineName();
    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->membership = $this->prophesize(OgMembershipInterface::class);
    $this->ogRole = $this->prophesize(RoleInterface::class);

    $this->groupTypeManager = $this->prophesize(GroupTypeManagerInterface::class);
    $this->groupTypeManager->isGroup($this->entityTypeId, $this->bundle)->willReturn(TRUE);

    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    // It is expected that any access check will retrieve the settings, because
    // it contains an option to give full access to to the group manager.
    $this->config = $this->addCache($this->prophesize(Config::class));
    $this->config->get('group_manager_full_access')->willReturn(FALSE);

    // Whether or not the user has access to a certain operation depends in part
    // on the 'group_manager_full_access' setting which is stored in config.
    // Since the access is cached, this means that from the point of view from
    // the caching system this access varies by the 'og.settings' config object
    // that contains this setting. It is hence expected that the cacheability
    // metadata is retrieved from the config object so it can be attached to the
    // access result object.
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('og.settings')->willReturn($this->config);

    $this->config->getCacheContexts()->willReturn([]);
    $this->config->getCacheTags()->willReturn([]);
    $this->config->getCacheMaxAge()->willReturn(0);

    // Create a mocked test user.
    $user_id = 2;
    $this->user = $this->prophesize(AccountInterface::class);
    $this->user->isAuthenticated()->willReturn(TRUE);
    $this->user->id()->willReturn($user_id);
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(FALSE);

    $this->group = $this->groupEntity()->reveal();

    $this->membershipManager = $this->prophesize(MembershipManagerInterface::class);
    $this->membershipManager->getMembership($this->group, $user_id)->willReturn($this->membership->reveal());
    $this->membershipManager->getMembership($this->group, $user_id, [OgMembershipInterface::STATE_ACTIVE])->willReturn($this->membership->reveal());
    $this->membershipManager->getGroupCount(Argument::any())->willReturn(1);
    $this->membership->getRoles()->willReturn([$this->ogRole->reveal()]);

    $this->groupAudienceHelper = $this->prophesize(OgGroupAudienceHelperInterface::class);

    // @todo: Move to test.
    $this->ogRole->isAdmin()->willReturn(FALSE);
    $this->ogRole->getPermissions()->willReturn(['update group']);

    // Mock all dependencies for the system under test.
    $account_proxy = $this->prophesize(AccountProxyInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $this->permissionManager = $this->prophesize(PermissionManager::class);

    // Instantiate the system under test.
    $this->ogAccess = new OgAccess(
      $config_factory->reveal(),
      $account_proxy->reveal(),
      $module_handler->reveal(),
      $this->groupTypeManager->reveal(),
      $this->permissionManager->reveal(),
      $this->membershipManager->reveal(),
      $this->groupAudienceHelper->reveal()
    );

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager->reveal());
    $container->set('config.factory', $config_factory->reveal());
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $container->set('module_handler', $this->prophesize(ModuleHandlerInterface::class)->reveal());
    $container->set('og.group_type_manager', $this->groupTypeManager->reveal());
    $container->set('og.membership_manager', $this->membershipManager->reveal());
    $container->set('og.group_audience_helper', $this->groupAudienceHelper->reveal());

    // This is for caching purposes only.
    $container->set('current_user', $this->user->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Returns a mocked test group.
   *
   * @param bool $is_owner
   *   Whether or not this test group should be owned by the test user which is
   *   used in the test.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   *   The test group.
   */
  protected function groupEntity($is_owner = FALSE) {
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->id()->willReturn($this->entityTypeId);

    $group_entity = $this->prophesize(EntityInterface::class);
    if ($is_owner) {
      $group_entity->willImplement(EntityOwnerInterface::class);
      // Our test user is hardcoded to have UID 2.
      $group_entity->getOwnerId()->willReturn(2);
    }
    $group_entity->getEntityType()->willReturn($entity_type);
    $group_entity->getEntityTypeId()->willReturn($this->entityTypeId);
    $group_entity->bundle()->willReturn($this->bundle);
    $group_entity->id()->willReturn($this->groupId);

    return $this->addCache($group_entity);
  }

  /**
   * Mocks the cache methods.
   */
  protected function addCache($prophecy) {
    $prophecy->getCacheContexts()->willReturn([]);
    $prophecy->getCacheTags()->willReturn([]);
    $prophecy->getCacheMaxAge()->willReturn(0);
    return $prophecy;
  }

  /**
   * Provides permissions to use in access tests.
   *
   * @return array
   *   An array of test permissions.
   */
  public function permissionsProvider() {
    return [
      // In the unit tests we don't really care about the permission name - it
      // can be an arbitrary string; except for
      // OgAccessTest::testUserAccessAdminPermission test which checks for
      // "administer group" permission.
      ['update group'],
      ['administer group'],
    ];
  }

}
