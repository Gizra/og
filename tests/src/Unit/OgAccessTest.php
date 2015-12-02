<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessTest.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
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

  /**
   * @coversDefaultmethod ::userAccess
   */
  public function testUserAccess() {
    $entity_type_id = $this->randomMachineName();
    $bundle = $this->randomMachineName();;

    $group_manager = $this->prophesize(GroupManager::class);
    $group_manager->isGroup($entity_type_id, $bundle)->willReturn(TRUE);

    $config = $this->prophesize(Config::class);
    $config->get('group_manager_full_access')->willReturn(TRUE);

    $config_factory = $this->prophesize(ConfigFactory::class);
    $config_factory->get('og.settings')->willReturn($config);

    $container = new ContainerBuilder();
    $container->set('og.group.manager', $group_manager->reveal());
    $container->set('config.factory', $config_factory);
    \Drupal::setContainer($container);

    $group_entity = $this->prophesize(EntityOwnerInterface::class);
    $group_entity->willImplement(EntityInterface::class);
    $group_entity->getEntityTypeId()->willReturn($entity_type_id);
    $group_entity->bundle()->willReturn($bundle);

    $user = $this->prophesize(AccountInterface::class);
    $user->id()->willReturn(1);

    OgAccess::userAccess($group_entity->reveal(), 'view', $user->reveal());
  }

}
