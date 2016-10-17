<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\og\ContextProvider\OgContext;
use Drupal\Tests\UnitTestCase;

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
  }

  /**
   * Tests retrieving group context during runtime.
   *
   * @param array $unqualified_context_ids
   *   The requested context IDs that are passed to ::getRuntimeContexts(). The
   *   context provider must only return contexts for those IDs.
   * @param string|false $expected_context
   *   The ID of the entity that is expected to be provided as group context, or
   *   FALSE if no context should be returned.
   *
   * @covers ::getRuntimeContexts
   *
   * @dataProvider getRuntimeContextsProvider
   */
  public function testGetRuntimeContexts(array $unqualified_context_ids, $expected_context) {
    $og_context = new OgContext($this->pluginManager->reveal(), $this->configFactory->reveal());

    $result = $og_context->getRuntimeContexts($unqualified_context_ids);

    // If no group context is expected to be returned, the result should be an
    // empty array.
    if ($expected_context === FALSE) {
      $this->assertEquals([], $result);
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
        // Nothing should be returned.
        FALSE,
      ],
    ];
  }

}
