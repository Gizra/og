<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->pluginManager = $this->prophesize(PluginManagerInterface::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);

    // Mock the string translation service on the container, this will cover
    // calls to $this->t().
    $container = new Container();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
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
   *     for further groups is over. Defaults to FALSE;
   * @param string|false $expected_context
   *   The ID of the entity that is expected to be provided as group context, or
   *   FALSE if no context should be returned.
   *
   * @covers ::getRuntimeContexts
   *
   * @dataProvider getRuntimeContextsProvider
   */
  public function testGetRuntimeContexts(array $unqualified_context_ids, array $group_resolvers, $expected_context) {
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
        ->will(function ($args) use ($group_resolver) {
          /** @var \Drupal\og\OgResolvedGroupCollectionInterface $collection */
          $collection = $args[0];
          foreach ($group_resolver['candidates'] as $candidate) {
            // @todo Pass EntityInterface objects to addGroup().
            $collection->addGroup($candidate);
          }
        });
      $this->pluginManager->createInstance($id)
        ->willReturn($plugin);
    }

    $og_context = new OgContext($this->pluginManager->reveal(), $this->configFactory->reveal());

    $result = $og_context->getRuntimeContexts($unqualified_context_ids);

    // If no group context is expected to be returned, the result should be an
    // empty array.
    if ($expected_context === FALSE) {
      $this->assertEquals([], $result);
    }
    else {
      $this->assertEquals($expected_context, $result['og']->getContextData());
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
          'route_group' => ['candidates' => 'node-1'],
        ],
        // The group of type 'node' was found.
        'node-1',
      ]
    ];
  }

}
