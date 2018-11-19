<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Tests if the cache is correctly invalidated on group change.
 *
 * @group og
 */
class CacheInvalidationOnGroupChangeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'og',
    'system',
    'user',
    'options'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');

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
    $group1 = EntityTest::create([
      'type' => 'group',
      'name' => $this->randomString(),
    ]);
    $group1->save();
    $group2 = EntityTest::create([
      'type' => 'group',
      'name' => $this->randomString(),
    ]);
    $group2->save();

    // Create a group content.
    $group_content = EntityTest::create([
      'type' => 'group_content',
      'name' => $this->randomString(),
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $group1->id(),
    ]);
    $group_content->save();

    // Cache some arbitrary data tagged with the OG group content tag.
    $bin = \Drupal::cache();
    $cid = strtolower($this->randomMachineName());
    $tags = Cache::buildTags('og-group-content', $group1->getCacheTagsToInvalidate());
    $bin->set($cid, $this->randomString(), Cache::PERMANENT, $tags);

    // Change the group content entity group. We're clearing first the static
    // cache of membership manager because in a real application, usually, the
    // static cache is warmed in a previous request, so that in the request
    // where the audience is changed, is already empty.
    $this->container->get('og.membership_manager')->reset();
    $group_content
      ->set(OgGroupAudienceHelperInterface::DEFAULT_FIELD, $group2->id())
      ->save();

    // Cache entries tagged with 'og-group-content:entity_type:{$group->id()}'
    // should have been invalidated at this point because the content members of
    // $group1 have changed.
    $this->assertFalse($bin->get($cid));
  }

}
