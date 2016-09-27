<?php

namespace Drupal\Tests\og\Unit\Plugin\OgGroupResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\Plugin\OgGroupResolver\RouteGroupResolver;
use Symfony\Component\Routing\Route;

/**
 * Tests the RouteGroupResolver plugin.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\OgGroupResolver\RouteGroupResolver
 */
class RouteGroupResolverTest extends OgRouteGroupResolverTestBase {

  /**
   * The mocked route matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $routeMatch;

  /**
   * The mocked OG group type manager.
   *
   * @var \Drupal\og\GroupTypeManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupTypeManager;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * A list of link templates that belong to our test entities.
   *
   * This mocks the data returned by EntityTypeInterface::getLinkTemplates().
   *
   * @var array
   *   A list of link templates, keyed by entity type ID.
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getLinkTemplates()
   */
  protected $linkTemplates = [
    'node' => [
      'canonical' => '/node/{node}',
      'delete-form' => '/node/{node}/delete',
      'edit-form' => '/node/{node}/edit',
      'version-history' => '/node/{node}/revisions',
      'revision' => '/node/{node}/revisions/{node_revision}/view',
    ],
    'entity_test' => [
      'canonical' => '/entity_test/{entity_test}',
      'add-form' => '/entity_test/add',
      'edit-form' => '/entity_test/manage/{entity_test}/edit',
      'delete-form' => '/entity_test/delete/entity_test/{entity_test}',
    ],
    'taxonomy_term' => [
      'canonical' => '/taxonomy/term/{taxonomy_term}',
      'delete-form' => '/taxonomy/term/{taxonomy_term}/delete',
      'edit-form' => '/taxonomy/term/{taxonomy_term}/edit',
    ],
  ];

  /**
   * Mocked test entities.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $testEntities;

  /**
   * Stores the entity types and bundles of the test entities.
   *
   * @var array
   *   An array of entity metadata, keyed by test entity ID. Each item is an
   *   array with the following keys:
   *   - type: The entity type ID.
   *   - bundle: The entity bundle.
   */
  protected $testEntityTypes = [
    'group' => ['type' => 'node', 'bundle' => 'group'],
    'group_content' => ['type' => 'entity_test', 'bundle' => 'group_content'],
    'non_group' => ['type' => 'taxonomy_term', 'bundle' => 'taxonomy_term'],
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Instantiate mocks of the classes that the plugins rely on.
    $this->routeMatch = $this->prophesize(RouteMatchInterface::class);
    $this->groupTypeManager = $this->prophesize(GroupTypeManager::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);

    // Prepare mocked group and group content entities as well as an entity that
    // is neither a group nor group content.
    foreach($this->testEntityTypes as $id => $metadata) {
      $entity = $this->prophesize(ContentEntityInterface::class);
      // In case this entity is questioned about its identity, it shall
      // willingly provide the information.
      $entity->getEntityTypeId()->willReturn($metadata['type']);
      $entity->bundle()->willReturn($metadata['bundle']);
      $this->testEntities[$id] = $entity->reveal();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected $className = RouteGroupResolver::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'route_group';

  /**
   * {@inheritdoc}
   *
   * @covers getGroups
   * @dataProvider getGroupsProvider
   */
  public function testGetGroups($path = NULL, $route_object_type = NULL, $expected = NULL) {
    $plugin = $this->getPluginInstance($this->getInjectedDependencies($path, $route_object_type));

    $expected = $expected ? [$this->testEntities[$expected]] : [];
    $this->assertEquals($expected, $plugin->getGroups());
  }

  /**
   * {@inheritdoc}
   */
  protected function getInjectedDependencies($path = NULL, $route_object_type = NULL) {
    if ($path) {
      // It is expected that the plugin will retrieve the current path from the
      // route matcher.
      $this->willRetrieveCurrentPathFromRouteMatcher($path);
      // It is expected that the plugin will retrieve the full list of content
      // entity paths, so it can check whether the current path is related to a
      // content entity.
      $this->willRetrieveContentEntityPaths();
    }

    if ($route_object_type) {
      // The plugin might retrieve the route object. This should only happen if
      // we are on an actual entity path.
      $this->mightRetrieveRouteObject($route_object_type);
      // If a route object is returned the plugin will need to inspect it to
      // check if it is a group.
      $this->mightCheckIfRouteObjectIsAGroup($route_object_type);
    }

    return [
      $this->routeMatch->reveal(),
      $this->groupTypeManager->reveal(),
      $this->entityTypeManager->reveal(),
    ];
  }

  /**
   * Adds an expectation that the current path will be retrieved from the route.
   *
   * @param string $path
   *   The path that will be retrieved.
   */
  protected function willRetrieveCurrentPathFromRouteMatcher($path) {
    /** @var \Symfony\Component\Routing\Route|\Prophecy\Prophecy\ObjectProphecy $route */
    $route = $this->prophesize(Route::class);
    $route
      ->getPath()
      ->willReturn($path)
      ->shouldBeCalled();
    $this->routeMatch
      ->getRouteObject()
      ->willReturn($route->reveal())
      ->shouldBeCalled();
  }

  /**
   * Adds an expectation that the plugin will retrieve a list of entity paths.
   *
   * The plugin need to match the current path to this list of entity paths to
   * see if we are currently on an entity path of a group or group content
   * entity.
   * In order to retrieve the content entity paths, the plugin will have to
   * request a full list of all entity types, then request the "link templates"
   * from the content entities.
   */
  protected function willRetrieveContentEntityPaths() {
    // Provide some mocked content entity types.
    $entity_types = [];
    foreach (array_keys($this->linkTemplates) as $entity_type_id) {
      $entity_type = $this->prophesize(EntityTypeInterface::class);
      // The plugin will need to know if this is a content entity, so we will
      // provide this information. We are not requiring this to be called since
      // there are other ways of determining this (e.g. `instanceof`).
      $entity_type->isSubclassOf(ContentEntityInterface::class)->willReturn(TRUE);

      // The plugin will need to inquire about the link templates that the
      // entity provides. This should be called.
      $entity_type->getLinkTemplates()
        ->willReturn($this->getLinkTemplates($entity_type_id))
        ->shouldBeCalled();
      $entity_types[$entity_type_id] = $entity_type->reveal();
    }
    $this->entityTypeManager->getDefinitions()
      ->willReturn($entity_types)
      ->shouldBeCalled();
  }

  /**
   * Adds an expectation that the plugin will (not) retrieve the route object.
   *
   * If the current path is an entity path, the plugin should retrieve the
   * entity from the route so it can check if the entity is a group. If we are
   * not, then it should not attempt to retrieve it.
   *
   * @param string|null $route_object_type
   *   The type of entity that is present on the current route, or NULL if we
   *   are not on a content entity path. The types may be any of the ones
   *   created in the test setup, e.g. 'group', 'group_content', 'non_group'.
   */
  protected function mightRetrieveRouteObject($route_object_type) {
    // The route object should only be retrieved if we are on a content entity
    // path.
    if ($route_object_type)  {
      $this->routeMatch->getParameter($this->testEntityTypes[$route_object_type]['type'])
        ->willReturn($this->testEntities[$route_object_type])
        ->shouldBeCalled();
    }
  }

  /**
   * Adds an expectation that checks if the route object is a group.
   *
   * If the plugin found a content entity on the route then it should check
   * whether the entity is a group or not. If no content entity was found, it
   * should not perform this check.
   *
   * @param string|null $route_object_type
   *   The type of entity that is present on the current route, or NULL if we
   *   are not on a content entity path. The types may be any of the ones
   *   created in the test setup, e.g. 'group', 'group_content', 'non_group'.
   */
  protected function mightCheckIfRouteObjectIsAGroup($route_object_type) {
    if ($route_object_type || TRUE)  {
      $entity_type_id = $this->testEntityTypes[$route_object_type]['type'];
      $bundle = $this->testEntityTypes[$route_object_type]['bundle'];
      $this->groupTypeManager->isGroup($entity_type_id, $bundle)
        ->willReturn($route_object_type === 'group')
        ->shouldBeCalled();
    }
  }

  /**
   * Returns a list of test entity types.
   *
   * This mimicks the data returned by EntityTypeManager::getDefinitions().
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   A list of mocked entity types.
   *
   * @see \Drupal\Core\Entity\EntityTypeManagerInterface::getDefinitions()
   */
  protected function getEntityTypes() {
    return [
      'node' => $this->prophesize(ContentEntityInterface::class)
    ];
  }

  /**
   * Returns a set of entity link templates for testing.
   *
   * This mimicks the data returned by EntityTypeInterface::getLinkTemplates().
   *
   * @param string $entity_type
   *   The entity type for which to return the link templates.
   *
   * @return array
   *   An array of link templates.
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getLinkTemplates()
   */
  protected function getLinkTemplates($entity_type) {
    return $this->linkTemplates[$entity_type];
  }

  /**
   * Data provider for testGetGroups().
   *
   * @see ::testGetGroups()
   */
  public function getGroupsProvider() {
    return [
      // Test that no groups are returned on a path that is not associated with
      // any entities.
      [
        // A path that is not associated with any entities.
        '/user/logout',
        // There is no entity on this route.
        NULL,
        // So the plugin should not return anything.
        NULL,
      ],
      // Test that if we are on the canonical entity page of a group, the
      // correct group is returned.
      [
        // We're on the canonical group entity page.
        '/node/{node}',
        // The test group is found on the route.
        'group',
        // The plugin should be able to figure out this is a group, and return
        // it.
        'group',
      ],
      // Test that if we are on the delete form of a group, the correct group is
      // returned.
      [
        '/node/{node}/delete',
        'group',
        'group',
      ],
      // Test that if we are on the canonical entity page of a group content
      // entity, no group should be returned.
      [
        '/entity_test/{entity_test}',
        'group_content',
        NULL,
      ],
      // Test that if we are on the delete form of a group content entity, no
      // group should be returned.
      [
        '/entity_test/delete/entity_test/{entity_test}',
        'group_content',
        NULL,
      ],
      // Test that if we are on the canonical entity page of an entity that is
      // neither a group nor group content, no group should be returned.
      [
        '/taxonomy/term/{taxonomy_term}',
        'non_group',
        NULL,
      ],
      // Test that if we are on the delete form of an entity that is neither a
      // group nor group content, no group should be returned.
      [
        '/taxonomy/term/{taxonomy_term}/delete',
        'non_group',
        NULL,
      ],
    ];
  }

}
