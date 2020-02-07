<?php

namespace Drupal\Tests\og\Unit\Cache\Context;

use Drupal\Tests\UnitTestCase;

/**
 * Base class for testing cache context services.
 */
abstract class OgCacheContextTestBase extends UnitTestCase {

  /**
   * Returns the instantiated cache context service which is being tested.
   *
   * @return \Drupal\Core\Cache\Context\CacheContextInterface
   *   The instantiated cache context service.
   */
  abstract protected function getCacheContext();

  /**
   * Return the context result.
   *
   * @return string
   *   The context result.
   */
  protected function getContextResult() {
    return $this->getCacheContext()->getContext();
  }

}
