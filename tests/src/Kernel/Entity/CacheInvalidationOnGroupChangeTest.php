<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Tests if group content listings are invalidated when group audience changes.
 *
 * @group og
 */
class CacheInvalidationOnGroupChangeTest extends KernelTestBase {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'og',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');

    $this->cache = $this->container->get('cache.default');

    // Add a OG group audience.
    Og::groupTypeManager()->addGroup('entity_test', 'group');
    $settings = [
      'field_storage_config' => [
        'field_name' => OgGroupAudienceHelperInterface::DEFAULT_FIELD,
        'settings' => [
          'target_type' => 'entity_test',
        ],
      ],
      'field_config' => [
        'label' => $this->randomString(),
        'settings' => [
          'handler_settings' => [
            'target_bundles' => ['group' => 'group'],
          ],
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test', 'group_content', $settings);
  }

  /**
   * Tests if the cache is correctly invalidated on group change.
   */
  public function testCacheInvalidationOnGroupChange() {
    // Create two groups.
    $groups = [];
    for ($i = 0; $i < 2; $i++) {
      $groups[$i] = EntityTest::create([
        'type' => 'group',
        'name' => $this->randomString(),
      ]);
      $groups[$i]->save();
    }

    // Create a group content entity that belong to the first group.
    $group_content = EntityTest::create([
      'type' => 'group_content',
      'name' => $this->randomString(),
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $groups[0]->id(),
    ]);
    $group_content->save();

    // Cache some arbitrary data tagged with the OG group content tags for both
    // groups.
    $this->populateCache($groups[0]);
    $this->populateCache($groups[1]);

    // Sanity check, the cached content listings of both groups should be
    // populated.
    $this->assertCachePopulated($groups[0]);
    $this->assertCachePopulated($groups[1]);

    // Change the label of group 1. This should not affect any of the cached
    // listings.
    $groups[0]->setName($this->randomString())->save();
    $this->assertCachePopulated($groups[0]);
    $this->assertCachePopulated($groups[1]);

    // Move the group content from group 1 to group 2. This should invalidate
    // the group content list cache tags of both groups.
    $group_content
      ->set(OgGroupAudienceHelperInterface::DEFAULT_FIELD, $groups[1]->id())
      ->save();

    // Cache entries tagged with 'og-group-content:entity_type:{$group->id()}'
    // should have been invalidated at this point because the content members of
    // both groups have changed.
    $this->assertCacheNotPopulated($groups[0]);
    $this->assertCacheNotPopulated($groups[1]);

    // Now populate both caches while including the cache tags of the group
    // itself. This can happen for example if a listing of group content is
    // shown that includes the group name in its content.
    $this->populateCache($groups[0], TRUE);
    $this->populateCache($groups[1], TRUE);

    // Change the label of group 1. This should invalidate the cache of the
    // group content listing for group 1, but not for group 2.
    $groups[0]->setName($this->randomString())->save();
    $this->assertCacheNotPopulated($groups[0]);
    $this->assertCachePopulated($groups[1]);
  }

  /**
   * Caches a listing of group content that belongs to the given group.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group for which to cache a group content listing.
   * @param bool $include_group_cache_tag
   *   Whether or not the group content listing is tagged with the group's cache
   *   tags.
   */
  protected function populateCache(ContentEntityInterface $group, bool $include_group_cache_tag = FALSE): void {
    $cid = $this->generateCid($group);
    $tags = Cache::buildTags('og-group-content', $group->getCacheTagsToInvalidate());
    if ($include_group_cache_tag) {
      $tags = Cache::mergeTags($tags, $group->getCacheTagsToInvalidate());
    }
    $this->cache->set($cid, $this->randomString(), Cache::PERMANENT, $tags);
  }

  /**
   * Generates a cache ID for a group content listing of the given group.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group for which to generate a group content listing cache ID.
   *
   * @return string
   *   The cache ID.
   */
  protected function generateCid(ContentEntityInterface $group): string {
    return implode(':', ['my_group_content_listing', $group->id()]);
  }

  /**
   * Checks if the group content listing cache for a given group is populated.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group for which to perform the check.
   */
  protected function assertCachePopulated(ContentEntityInterface $group): void {
    $this->assertNotEmpty($this->getCachedData($group));
  }

  /**
   * Checks if the group content listing cache for a given group is unpopulated.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group for which to perform the check.
   */
  protected function assertCacheNotPopulated(ContentEntityInterface $group): void {
    $this->assertFalse($this->getCachedData($group));
  }

  /**
   * Returns the cached group content listing for a given group, if available.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group for which to return the cached group content listing.
   *
   * @return false|object
   *   The cache item or FALSE on failure.
   */
  protected function getCachedData(ContentEntityInterface $group) {
    return $this->cache->get($this->generateCid($group));
  }

}
