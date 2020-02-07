<?php

namespace Drupal\Tests\og\Unit\Cache\Context;

use Drupal\Core\Entity\EntityInterface;
use Drupal\og\OgContextInterface;

/**
 * Base class for testing cache contexts that rely on OgContext.
 *
 * Use this for testing cache contexts that vary by the group that is active in
 * the current context, as determined by OgContext::getGroup().
 *
 * @see \Drupal\og\OgContextInterface
 */
abstract class OgContextCacheContextTestBase extends OgCacheContextTestBase {

  /**
   * The mocked OG context service.
   *
   * @var \Drupal\og\OgContextInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogContext;

  /**
   * A mocked group entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->ogContext = $this->prophesize(OgContextInterface::class);
    $this->group = $this->prophesize(EntityInterface::class);
  }

  /**
   * Tests the result of the cache context service with active context objects.
   *
   * This tests the most common use case: the service retrieves data from the
   * active context, and will be able to provide a relevant cache context string
   * in accordance with the provided data.
   *
   * @param mixed $context
   *   Data used to set up the expectations of the context objects. See
   *   setupExpectedContext().
   * @param string $expected_result
   *   The cache context string which is expected to be returned by the service
   *   under test.
   *
   * @covers ::getContext
   * @dataProvider contextProvider
   */
  public function testWithContext($context, $expected_result) {
    $this->setupExpectedContext($context);

    $result = $this->getContextResult();
    $this->assertEquals($expected_result, $result);
  }

  /**
   * Tests the result of the cache context service without active context.
   *
   * @covers ::getContext
   */
  abstract public function testWithoutContext();

  /**
   * Provides test data for the test with active context objects.
   *
   * @return array
   *   An array of test data arrays, each array having two elements:
   *   1. The test data that is used to set up the active context.
   *   2. The cache context string that is expected to be returned by the cache
   *      context service being tested.
   *
   * @see ::testWithContext()
   */
  abstract public function contextProvider();

  /**
   * Set up expectations for tests that have an active context object.
   *
   * @param mixed $context
   *   The test data for the active context, as provided by contextProvider().
   *
   * @see ::contextProvider()
   */
  abstract protected function setupExpectedContext($context);

  /**
   * Sets an expectation that OgContext will return the given group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group to return, or NULL if no group is expected to be returned by
   *   OgContext.
   */
  protected function expectGroupContext(EntityInterface $group = NULL) {
    $this->ogContext->getGroup()->willReturn($group);
  }

}
