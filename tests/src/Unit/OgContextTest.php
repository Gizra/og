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
   *
   * @covers ::getRuntimeContexts
   *
   * @dataProvider getRuntimeContextsProvider
   */
  public function testGetRuntimeContexts(array $unqualified_context_ids, array $group_resolvers, $expected_context) {
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
            $collection->addGroup($entities[$candidate]);
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
      $this->assertEquals($expected_context_entity, $result['og']->getContextData()->getValue());
      // @todo Test the cacheability metadata.
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
      ],
      // "Normal" test case: a single group was found in context. For this test
      // we simulate that a single group of type 'node' was found.
      [
        // The list of context IDs that are requested contains 'og'.
        ['node', 'og'],
        // Simulate 1 group resolver that returns 1 result.
        [
          'route_group' => ['candidates' => ['node-0']],
        ],
        // The group of type 'node' was found.
        'node-0',
      ],
    ];
  }

}
