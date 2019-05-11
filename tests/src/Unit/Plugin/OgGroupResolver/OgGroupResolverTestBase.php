<?php

namespace Drupal\Tests\og\Unit\Plugin\OgGroupResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Base class for testing OgGroupResolver plugins.
 *
 * @group og
 */
abstract class OgGroupResolverTestBase extends UnitTestCase {

  /**
   * The fully qualified class name of the plugin under test.
   *
   * @var string
   */
  protected $className;

  /**
   * The ID of the plugin under test.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Mocked test entities.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $testEntities;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The OG group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupAudienceHelper;

  /**
   * The mocked OG group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupTypeManager;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $membershipManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Instantiate mocks of the classes that the plugins rely on.
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->groupAudienceHelper = $this->prophesize(OgGroupAudienceHelperInterface::class);
    $this->groupTypeManager = $this->prophesize(GroupTypeManagerInterface::class);
    $this->membershipManager = $this->prophesize(MembershipManagerInterface::class);

    // Create mocked test entities.
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $test_entities */
    $test_entities = [];
    foreach ($this->getTestEntityProperties() as $id => $properties) {
      $entity_type_id = $properties['type'];
      $bundle_id = $properties['bundle'];
      $is_group = !empty($properties['group']);
      $is_group_content = !empty($properties['group_content']);

      $entity = $this->createMockedEntity($id, $properties);
      $test_entities[$id] = $entity->reveal();

      // It is not being tight lipped about whether it is a group or group
      // content.
      $this->groupTypeManager->isGroup($entity_type_id, $bundle_id)
        ->willReturn($is_group);
      $this->groupAudienceHelper->hasGroupAudienceField($entity_type_id, $bundle_id)
        ->willReturn($is_group_content);

      // If the entity is group content it will spill the beans on which groups
      // it belongs to.
      if ($is_group_content) {
        $groups = [];
        foreach ($properties['group_content'] as $group_id) {
          $group = $test_entities[$group_id];
          $groups[$group->getEntityTypeId()][$group->id()] = $group;
        }
        $this->membershipManager->getGroups($entity)
          ->willReturn($groups);
      }
    }
    $this->testEntities = $test_entities;
  }

  /**
   * Tests the groups that are resolved by the plugin.
   *
   * @dataProvider resolveProvider
   * @covers ::resolve()
   */
  abstract public function testResolve();

  /**
   * Tests if the plugin is able to stop the group resolving process.
   *
   * @covers ::isPropagationStopped
   * @covers ::stopPropagation
   */
  public function testStopPropagation() {
    $plugin = $this->getPluginInstance();

    // Initially propagation should not be stopped.
    $this->assertFalse($plugin->isPropagationStopped());

    // Test if propagation can be stopped.
    $plugin->stopPropagation();
    $this->assertTrue($plugin->isPropagationStopped());
  }

  /**
   * Returns properties used to create mock test entities.
   *
   * This is used to facilitate referring to entities in data providers. Since a
   * data provider is called before the test setup runs, we cannot return actual
   * entities in the data provider. Instead the data provider can refer to these
   * test entities by ID, and the actual entity mocks will be generated in the
   * test setup.
   *
   * The test groups should be declared first, the group content last.
   *
   * @return array
   *   An array of entity metadata, keyed by test entity ID. Each item is an
   *   array with the following keys:
   *   - type (required): The entity type ID.
   *   - bundle (required): The entity bundle.
   *   - group (optional): Whether or not the entity is a group.
   *   - group_content (optional): An array containing IDs of groups this group
   *     content belongs to.
   */
  abstract protected function getTestEntityProperties();

  /**
   * Returns an instance of the plugin under test.
   *
   * @return \Drupal\og\OgGroupResolverInterface
   *   The plugin under test.
   */
  protected function getPluginInstance() {
    $args = array_merge([
      [],
      $this->pluginId,
      [
        'id' => $this->pluginId,
        'class' => $this->className,
        'provider' => 'og',
      ],
    ], $this->getInjectedDependencies());
    return new $this->className(...$args);
  }

  /**
   * Returns the mocked classes that the plugin depends on.
   *
   * @return array
   *   The mocked dependencies.
   */
  protected function getInjectedDependencies() {
    return [];
  }

  /**
   * Creates a mocked content entity to use in the test.
   *
   * @param string $id
   *   The entity ID to assign to the mocked entity.
   * @param array $properties
   *   An associative array of properties to assign to the mocked entity, with
   *   the following keys:
   *   - type: The entity type.
   *   - bundle: The entity bundle.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|\Prophecy\Prophecy\ObjectProphecy
   *   The mocked entity.
   */
  protected function createMockedEntity($id, array $properties) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface|\Prophecy\Prophecy\ObjectProphecy $entity */
    $entity = $this->prophesize(ContentEntityInterface::class);

    // In case this entity is questioned about its identity, it shall
    // willingly pony up the requested information.
    $entity->id()->willReturn($id);
    $entity->getEntityTypeId()->willReturn($properties['type']);
    $entity->bundle()->willReturn($properties['bundle']);

    return $entity;
  }

}
