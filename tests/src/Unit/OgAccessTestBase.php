<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessTestBase.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\og\OgAccess;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\GroupManager;
use Prophecy\Argument;

class OgAccessTestBase extends UnitTestCase {

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

  /**
   * @var \Drupal\og\GroupManager
   */
  protected $groupManager;

  public function setUp() {
    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();

    $this->groupManager = $this->prophesize(GroupManager::class);
    $this->groupManager->isGroup($this->entityTypeId, $this->bundle)->willReturn(TRUE);

    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $this->config = $this->addCache($this->prophesize(Config::class));
    $this->config->get('group_manager_full_access')->willReturn(FALSE);

    $config_factory = $this->prophesize(ConfigFactory::class);
    $config_factory->get('og.settings')->willReturn($this->config);

    $this->user = $this->prophesize(AccountInterface::class);
    $this->user->isAuthenticated()->willReturn(TRUE);
    $this->user->id()->willReturn(2);
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(FALSE);

    $container = new ContainerBuilder();
    $container->set('og.group.manager', $this->groupManager->reveal());
    $container->set('cache_contexts_manager', $cache_contexts_manager->reveal());
    $container->set('config.factory', $config_factory->reveal());
    $container->set('module_handler', $this->prophesize(ModuleHandlerInterface::class)->reveal());
    // This is for caching purposes only.
    $container->set('current_user', $this->user->reveal());
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

  public function operationProvider() {
    return [
      ['view'],
      ['update'],
      ['administer group'],
    ];
  }

}
