<?php

namespace Drupal\Tests\og\Unit\Cache\Context;

use Drupal\og\Cache\Context\OgGroupContextCacheContext;

/**
 * Tests the OG group context cache context.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Cache\Context\OgGroupContextCacheContext
 */
class OgGroupContextCacheContextTest extends OgContextCacheContextTestBase {

  /**
   * Tests getting cache context when there is no matching group on the route.
   *
   * @covers ::getContext
   */
  public function testWithoutContext() {
    $this->expectGroupContext();

    $result = $this->getContextResult();
    $this->assertEquals(OgGroupContextCacheContext::NO_CONTEXT, $result);
  }

  /**
   * {@inheritdoc}
   */
  protected function setupExpectedContext($context) {
    if ($context) {
      $this->group->getEntityTypeId()
        ->willReturn($context['entity_type'])
        ->shouldBeCalled();
      $this->group->id()
        ->willReturn($context['id'])
        ->shouldBeCalled();
      $this->expectGroupContext($this->group->reveal());
    }
    else {
      $this->expectGroupContext();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCacheContext() {
    return new OgGroupContextCacheContext($this->ogContext->reveal());
  }

  /**
   * {@inheritdoc}
   */
  public function contextProvider() {
    return [
      // Test the expected result if no valid group exists in the active
      // context.
      [
        FALSE,
        OgGroupContextCacheContext::NO_CONTEXT,
      ],
      // Test having an entity with a numeric ID present in the active context.
      [
        [
          'entity_type' => 'node',
          'id' => 3,
        ],
        'node:3',
      ],
      // Test having an entity with a string ID present in the active context.
      [
        [
          'entity_type' => 'entity_test',
          'id' => 'Shibo Yangcong-San',
        ],
        'entity_test:Shibo Yangcong-San',
      ],
    ];
  }

}
