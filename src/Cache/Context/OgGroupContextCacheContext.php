<?php

namespace Drupal\og\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\og\OgContextInterface;

/**
 * Defines a cache context service for the currently active group.
 *
 * This uses OgContext to determine the active group. Potential use cases for
 * this cache context are elements on the page that vary by the active group,
 * for example a group header, or a block showing recent group content.
 *
 * Cache context ID: 'og_group_context'
 */
class OgGroupContextCacheContext implements CacheContextInterface {

  /**
   * The string to return when no context is found.
   */
  const NO_CONTEXT = 'none';

  /**
   * The OG context provider.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $ogContext;

  /**
   * Constructs a new UserCacheContextBase class.
   *
   * @param \Drupal\og\OgContextInterface $og_context
   *   The OG context provider.
   */
  public function __construct(OgContextInterface $og_context) {
    $this->ogContext = $og_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('OG active group');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // Do not provide a cache context if there is no group in the current
    // context.
    $group = $this->ogContext->getGroup();
    if (empty($group)) {
      return self::NO_CONTEXT;
    }

    // Compose a cache context string that consists of the entity type ID and
    // the entity ID of the active group.
    return implode(':', [$group->getEntityTypeId(), $group->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
