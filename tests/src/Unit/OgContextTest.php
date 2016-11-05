<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\og\ContextProvider\OgContext;
use Drupal\og\OgGroupResolverInterface;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Container;

/**
 * Tests the OgContext context provider.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\ContextProvider\OgContext
 */
class OgContextTest extends UnitTestCase {

  /**
   * A mocked plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $pluginManager;

  /**
   * A mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactory;

  /**
   * A mocked typed data manager service.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $typedDataManager;

  /**
   * A mocked typed data definition.
   *
   * @var \Drupal\Core\Entity\TypedData\EntityDataDefinition|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $dataDefinition;

  /**
   * A mocked typed data object.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $typedData;

  /**
   * An array of mocked test entities.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]|\Prophecy\Prophecy\ObjectProphecy[]
   */
  protected $entities;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->pluginManager = $this->prophesize(PluginManagerInterface::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->typedDataManager = $this->prophesize(TypedDataManagerInterface::class);
    $this->dataDefinition = $this->prophesize(EntityDataDefinition::class);
    $this->typedData = $this->prophesize(TypedDataInterface::class);

    // When a ContextProvider creates a new Context object and sets the context
    // value on it, the Context object will use the typed data manager service
    // to get a DataDefinition object, which is an abstracted representation of
    // the data. Mock the method calls that are used during creation of this
    // DataDefinition object. In the case of OgContext this will return an
    // entity.
    $this->dataDefinition->setLabel(Argument::any())->willReturn($this->dataDefinition);
    $this->dataDefinition->setDescription(Argument::any())->willReturn($this->dataDefinition);
    $this->dataDefinition->setRequired(Argument::any())->willReturn($this->dataDefinition);
    $this->dataDefinition->getConstraints()->willReturn([]);
    $this->dataDefinition->setConstraints(Argument::any())->willReturn($this->dataDefinition);

    $this->typedDataManager->createDataDefinition('entity')
      ->willReturn($this->dataDefinition->reveal());

    // Mock the string translation service on the container, this will cover
    // calls to $this->t().
    $container = new Container();
    $container->set('string_translation', $this->getStringTranslationStub());

    // Put the mocked typed data manager service on the container. This is used
    // to set the context value.
    $container->set('typed_data_manager', $this->typedDataManager->reveal());
    \Drupal::setContainer($container);

    // Create 2 mock entities each for node, block_content and entity_test
    // entities.
    foreach (['node', 'block_content', 'entity_test'] as $type) {
      for ($i = 0; $i < 2; $i++) {
        $id = "$type-$i";
        $entity = $this->prophesize(ContentEntityInterface::class);
        $entity->id()->willReturn($id);
        $entity->getEntityTypeId()->willReturn($type);
        $entity->getCacheContexts()->willReturn([]);
        $entity->getCacheTags()->willReturn([]);
        $entity->getCacheMaxAge()->willReturn(0);
        $this->entities[$id] = $entity->reveal();
      }
    }
  }

  /**
   * Tests retrieving group context during runtime.
   *
   * @param array $unqualified_context_ids
   *   The requested context IDs that are passed to ::getRuntimeContexts(). The
   *   context provider must only return contexts for those IDs.
   * @param array $group_resolvers
   *   An array of group resolver plugins that are used in the test case,
   *   ordered by priority. Each element is an array of plugin behaviors, with
   *   the following keys:
   *   - candidates: an array of group context candidates that the plugin adds
   *     to the collection of resolved groups.
   *   - stop_propagation: whether or not the plugin declares that the search
   *     for further groups is over. Defaults to FALSE.
   * @param string|false $expected_context
   *   The ID of the entity that is expected to be provided as group context, or
   *   FALSE if no context should be returned.
   * @param string[] $expected_cache_contexts
   *   An array of cache context IDs which are expected to be returned as
   *   cacheability metadata.
   *
   * @covers ::getRuntimeContexts
   *
   * @dataProvider getRuntimeContextsProvider
   */
  public function testGetRuntimeContexts(array $unqualified_context_ids, array $group_resolvers, $expected_context, array $expected_cache_contexts) {
    // Make the test entities available in the local scope so we can use it in
    // anonymous functions.
    $entities = $this->entities;

    // Translate the ID of the expected context to the actual test entity.
    $expected_context_entity = !empty($expected_context) ? $entities[$expected_context] : NULL;

    // Return the list of OgGroupResolver plugins that are supplied in the test
    // case. These are expected to be retrieved from config.
    $group_resolvers_config = $this->prophesize(ImmutableConfig::class);
    $group_resolvers_config->get('group_resolvers')
      ->willReturn(array_keys($group_resolvers));
    $this->configFactory->get('og.settings')
      ->willReturn($group_resolvers_config);

    // Mock the OgGroupResolver plugins.
    foreach ($group_resolvers as $id => $group_resolver) {
      $plugin = $this->prophesize(OgGroupResolverInterface::class);
      $plugin->isPropagationStopped()
        ->willReturn(!empty($group_resolver['stop_propagation']));
      $plugin->resolve(Argument::type(OgResolvedGroupCollectionInterface::class))
        ->will(function ($args) use ($entities, $group_resolver) {
          /** @var \Drupal\og\OgResolvedGroupCollectionInterface $collection */
          $collection = $args[0];
          foreach ($group_resolver['candidates'] as $candidate) {
            $entity = $entities[$candidate['entity']];
            $cache_contexts = $candidate['cache_contexts'];
            $collection->addGroup($entity, $cache_contexts);
          }
        });
      $this->pluginManager->createInstance($id)
        ->willReturn($plugin);
    }

    // It is expected that the correct resolved group will be set on the Context
    // object.
    if ($expected_context !== FALSE) {
      $this->typedDataManager->create($this->dataDefinition, $entities[$expected_context])
        ->shouldBeCalled()
        ->willReturn($this->typedData->reveal());

      // If the group is correctly set as on the Context object, then it is
      // reasonable to expect that it will be returned as a typed data object
      // that will give back the group when it is asked for it.
      $this->typedData->getValue()
        ->willReturn($expected_context_entity);
    }

    $og_context = new OgContext($this->pluginManager->reveal(), $this->configFactory->reveal());

    $result = $og_context->getRuntimeContexts($unqualified_context_ids);

    // If no group context is expected to be returned, the result should be an
    // empty array.
    if ($expected_context === FALSE) {
      $this->assertEquals([], $result);
    }
    else {
      // Check that the 'og' context is populated.
      $this->assertNotEmpty($result['og']);

      // Check that the correct group is set as the context value.
      $this->assertEquals($expected_context_entity, $result['og']->getContextData()->getValue());

      // Check that the correct cache context IDs are set as cacheability
      // metadata.
      $this->assertEquals($expected_cache_contexts, array_values($result['og']->getCacheContexts()));
    }

  }

  /**
   * Data provider for ::testGetRuntimeContexts().
   *
   * @return array
   *   An array of test data.
   */
  public function getRuntimeContextsProvider() {
    return [
      // When 'og' is not present in the list of requested context IDs, then it
      // should not return any context.
      [
        // A list of context IDs that does not include 'og'.
        ['node', 'current_user'],
        // It is irrelevant which group resolvers are configured when we are not
        // requesting the OG context.
        [],
        // Nothing should be returned.
        FALSE,
        // Cache contexts are not relevant for this test.
        [],
      ],

      // "Normal" test case: a single group was found in context. For this test
      // we simulate that a single group of type 'node' was found.
      [
        // The list of context IDs that are requested contains 'og'.
        ['node', 'og'],
        // Simulate 1 group resolver that returns 1 result.
        [
          'route_group' => [
            'candidates' => [
              [
                'entity' => 'node-0',
                'cache_contexts' => ['route'],
              ],
            ],
          ],
        ],
        // It is expected that the group of type 'node' will be returned as
        // group context.
        'node-0',
        // The cache context of the group will be returned as cacheability
        // metadata.
        ['route'],
      ],

      // Two group resolver plugins which each return a single result. The
      // result and cache contexts from the first plugin should be taken because
      // it has higher priority.
      [
        ['og', 'user'],
        [
          'route_group' => [
            'candidates' => [
              [
                'entity' => 'block_content-0',
                'cache_contexts' => ['route'],
              ],
            ],
          ],
          'user_access' => [
            'candidates' => [
              [
                'entity' => 'entity_test-0',
                'cache_contexts' => ['user'],
              ],
            ],
          ],
        ],
        'block_content-0',
        ['route'],
      ],

      // Three group resolver plugins which all return different groups, but one
      // of them is returned by two plugins. This should win and have its cache
      // tags merged.
      [
        ['domain', 'user', 'og'],
        [
          'route_group' => [
            'candidates' => [
              [
                'entity' => 'node-1',
                'cache_contexts' => ['route'],
              ],
            ],
          ],
          'request_query_argument' => [
            'candidates' => [
              [
                'entity' => 'entity_test-1',
                'cache_contexts' => ['route'],
              ],
              [
                'entity' => 'node-0',
                'cache_contexts' => ['url'],
              ],
              [
                'entity' => 'block_content-1',
                'cache_contexts' => ['url'],
              ],
            ],
          ],
          'user_access' => [
            'candidates' => [
              [
                'entity' => 'block_content-0',
                'cache_contexts' => ['user'],
              ],
              [
                'entity' => 'block_content-1',
                'cache_contexts' => ['user'],
              ],
            ],
          ],
        ],
        'block_content-1',
        ['url', 'user'],
      ],

      // The same test case as the previous one, but now the first plugin
      // stops propagation. The results from the other plugins should be
      // ignored.
      [
        ['domain', 'user', 'og'],
        [
          'route_group' => [
            'candidates' => [
              [
                'entity' => 'node-1',
                'cache_contexts' => ['route'],
              ],
            ],
            'stop_propagation' => TRUE,
          ],
          'request_query_argument' => [
            'candidates' => [
              [
                'entity' => 'entity_test-1',
                'cache_contexts' => ['route'],
              ],
              [
                'entity' => 'node-0',
                'cache_contexts' => ['url'],
              ],
              [
                'entity' => 'block_content-1',
                'cache_contexts' => ['url'],
              ],
            ],
          ],
          'user_access' => [
            'candidates' => [
              [
                'entity' => 'block_content-0',
                'cache_contexts' => ['user'],
              ],
              [
                'entity' => 'block_content-1',
                'cache_contexts' => ['user'],
              ],
            ],
          ],
        ],
        'node-1',
        ['route'],
      ],
    ];
  }

}
