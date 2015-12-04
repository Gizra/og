<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessTest.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\GroupManager;
use Drupal\og\OgAccess;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;

/**
 * @group og
 * @coversDefaultClass \Drupal\og\OgAccess
 */
class OgAccessTest extends UnitTestCase {

  protected $config;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * @var string
   */
  protected $entityTypeId;

  /**
   * @var string
   */
  protected $bundle;

  public function setUp() {
    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();

    $group_manager = $this->prophesize(GroupManager::class);

    $this->isGroup = $group_manager->isGroup($this->entityTypeId, $this->bundle);
    $this->isGroup->willReturn(TRUE);

    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens(["user.permissions"])->willReturn(TRUE);

    $this->config = $this->prophesize(Config::class);
    $this->config->get('group_manager_full_access')->willReturn(FALSE);

    $config_factory = $this->prophesize(ConfigFactory::class);
    $config_factory->get('og.settings')->willReturn($this->config);

    $this->user = $this->prophesize(AccountInterface::class);
    $this->user->id()->willReturn(2);
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(FALSE);

    $container = new ContainerBuilder();
    $container->set('og.group.manager', $group_manager->reveal());
    $container->set('cache_contexts_manager', $cache_contexts_manager->reveal());
    $container->set('config.factory', $config_factory->reveal());
    $container->set('module_handler', $this->prophesize(ModuleHandlerInterface::class)->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @param bool $is_owner
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function groupEntity($is_owner = FALSE) {
    $group_entity = $this->prophesize(EntityInterface::class);
    if ($is_owner) {
      $group_entity->willImplement(EntityOwnerInterface::class);
      $group_entity->getOwnerId()->willReturn(2);
    }
    $group_entity->getEntityTypeId()->willReturn($this->entityTypeId);
    $group_entity->bundle()->willReturn($this->bundle);
    $group_entity->id()->willReturn($this->randomMachineName());
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
   * @coversDefaultmethod ::userAccess
   * @dataProvider operationProvider
   */
  public function testNotAGroup($operation) {
    $this->isGroup->willReturn(FALSE);
    $user_access = OgAccess::userAccess($this->groupEntity()->reveal(), $operation);
    $this->assertTrue($user_access->isNeutral());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider operationProvider
   */
  public function testForbiddenByDefault($operation) {
    $user_access = OgAccess::userAccess($this->groupEntity()->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isForbidden());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider operationProvider
   */
  public function testUser1($operation) {
    $this->user->id()->willReturn(1);
    $user_access = OgAccess::userAccess($this->groupEntity()->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider operationProvider
   */
  public function testAdminPermission($operation) {
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(TRUE);
    $user_access = OgAccess::userAccess($this->groupEntity()->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider operationProvider
   */
  public function testOwner($operation) {
    $this->config->get('group_manager_full_access')->willReturn(TRUE);
    $this->addCache($this->config);
    $user_access = OgAccess::userAccess($this->groupEntity(TRUE)->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider operationProvider
   */
  public function testOgUserAccessAlter($operation) {
    $permissions[OgAccess::ADMINISTER_GROUP_PERMISSION] = TRUE;
    \Drupal::getContainer()->set('module_handler', new OgAccessTestAlter($permissions));
    $group_entity = $this->groupEntity();
    $group_entity->id()->willReturn(mt_rand(5, 10));
    $user_access = OgAccess::userAccess($group_entity->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

  public function operationProvider() {
    return [
      ['view'],
      ['update'],
      ['delete'],
    ];
  }
}

class OgAccessTestAlter {
  public function __construct($data) {
    $this->data = $data;
  }
  public function alter($op, &$data) {
    $data = $this->data;
  }
}
