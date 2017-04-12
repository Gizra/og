<?php

namespace Drupal\Tests\og\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Event\DefaultRoleEvent;
use Drupal\og\Event\DefaultRoleEventInterface;
use Drupal\og\Og;
use Drupal\og\OgRoleInterface;

/**
 * Tests the implementation of the DefaultRoleEvent in the 'og' module.
 *
 * @group og
 */
class DefaultRoleEventIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'og', 'system', 'user', 'field'];

  /**
   * The Symfony event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The group bundle ID of the test group.
   *
   * @var string
   */
  protected $groupBundleId;

  /**
   * The OG role storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ogRoleStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->eventDispatcher = $this->container->get('event_dispatcher');
    $this->ogRoleStorage = $this->container->get('entity_type.manager')->getStorage('og_role');

    // Create a group entity type. Note that since we are using the EntityTest
    // entity we don't actually need to create the group bundle. EntityTest does
    // not have real bundles, it just pretends it does.
    $this->groupBundleId = $this->randomMachineName();
    Og::groupTypeManager()->addGroup('entity_test', $this->groupBundleId);
  }

  /**
   * Tests that OG correctly provides the group administrator default role.
   */
  public function testPermissionEventIntegration() {
    /** @var \Drupal\og\Event\DefaultRoleEvent $event */
    $event = new DefaultRoleEvent();

    // Query the event listener directly to see if the administrator role is
    // present.
    $this->eventDispatcher->dispatch(DefaultRoleEventInterface::EVENT_NAME, $event);
    $this->assertEquals([OgRoleInterface::ADMINISTRATOR], array_keys($event->getRoles()));

    // Check that the role was created with the correct values.
    $role = $event->getRole(OgRoleInterface::ADMINISTRATOR);
    $this->assertEquals(OgRoleInterface::ADMINISTRATOR, $role->getName());
    $this->assertEquals('Administrator', $role->getLabel());
    $this->assertEquals(OgRoleInterface::ROLE_TYPE_STANDARD, $role->getRoleType());
    $this->assertTrue($role->isAdmin());

    // Check that the per-group-type default roles are populated.
    $expected_roles = [
      OgRoleInterface::ANONYMOUS,
      OgRoleInterface::AUTHENTICATED,
      OgRoleInterface::ADMINISTRATOR,
    ];
    $actual_roles = $this->ogRoleStorage->loadByProperties([
      'group_type' => 'entity_test',
      'group_bundle' => $this->groupBundleId,
    ]);

    $this->assertEquals(count($expected_roles), count($actual_roles));

    foreach ($expected_roles as $expected_role) {
      // The role ID consists of the entity type, bundle and role name.
      $expected_key = implode('-', [
        'entity_test',
        $this->groupBundleId,
        $expected_role,
      ]);
      $this->assertArrayHasKey($expected_key, $actual_roles);
    }
  }

}
