<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\node\Entity\NodeType;
use Drupal\og\GroupTypeManager;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Tests retrieving group content bundles by group bundles and vice versa.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\GroupTypeManager
 */
class GetBundleByBundleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block_content',
    'field',
    'node',
    'og',
    'system',
    'user',
  ];

  /**
   * Test groups.
   *
   * @var \Drupal\Core\Entity\EntityInterface[][]
   */
  protected $groups = [];

  /**
   * Test group content.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $groupContent;

  /**
   * The group manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('node');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    $this->groupTypeManager = $this->container->get('og.group_type_manager');
    $this->cache = $this->container->get('cache.data');

    // Create four groups of two different entity types.
    for ($i = 0; $i < 2; $i++) {
      $bundle = "group_$i";
      NodeType::create([
        'name' => $this->randomString(),
        'type' => $bundle,
      ])->save();
      Og::groupTypeManager()->addGroup('node', $bundle);

      BlockContentType::create(['id' => $bundle])->save();
      Og::groupTypeManager()->addGroup('block_content', $bundle);
    }
  }

  /**
   * Tests retrieval of bundles that are referenc[ed|ing] bundles.
   *
   * This tests the retrieval of the relations between groups and group content
   * and vice versa. The retrieval of groups that are referenced by group
   * content is done by
   * GroupTypeManagerInterface::getGroupBundleIdsByGroupContenBundle()
   * while GroupTypeManagerInterface::getGroupContentBundleIdsByGroupBundle()
   * handles the opposite case.
   *
   * Both methods are tested here in a single test since they are very similar,
   * and not having to set up the entire relationship structure twice reduces
   * the total test running time.
   *
   * @param array $relationships
   *   An array indicating the relationships between groups and group content
   *   bundles that need to be set up in the test.
   * @param array $expected_group_by_group_content
   *   An array containing the expected results for the call to
   *   getGroupBundleIdsByGroupContentBundle().
   * @param array $expected_group_content_by_group
   *   An array containing the expected results for the 4 calls to
   *   getGroupContentBundleIdsByGroupBundle() that will be made in the test.
   *
   * @covers ::getGroupBundleIdsByGroupContentBundle
   * @covers ::getGroupContentBundleIdsByGroupBundle
   *
   * @dataProvider getBundleIdsByBundleProvider
   */
  public function testGetBundleIdsByBundle(array $relationships, array $expected_group_by_group_content, array $expected_group_content_by_group) {
    // Set up the relations as indicated in the test.
    foreach ($relationships as $group_content_entity_type_id => $group_content_bundle_ids) {
      foreach ($group_content_bundle_ids as $group_content_bundle_id => $group_audience_fields) {
        switch ($group_content_entity_type_id) {
          case 'node':
            NodeType::create([
              'name' => $this->randomString(),
              'type' => $group_content_bundle_id,
            ])->save();
            break;

          case 'block_content':
            BlockContentType::create(['id' => $group_content_bundle_id])->save();
            break;
        }
        foreach ($group_audience_fields as $group_audience_field_key => $group_audience_field_data) {
          foreach ($group_audience_field_data as $group_entity_type_id => $group_bundle_ids) {
            $settings = [
              'field_name' => 'group_audience_' . $group_audience_field_key,
              'field_storage_config' => [
                'settings' => [
                  'target_type' => $group_entity_type_id,
                ],
              ],
            ];

            if (!empty($group_bundle_ids)) {
              $settings['field_config'] = [
                'settings' => [
                  'handler' => 'default',
                  'handler_settings' => [
                    'target_bundles' => array_combine($group_bundle_ids, $group_bundle_ids),
                  ],
                ],
              ];
            }
            Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, $group_content_entity_type_id, $group_content_bundle_id, $settings);
          }
        }
      }
    }

    // Test ::getGroupBundleIdsByGroupContentBundle().
    foreach ($expected_group_by_group_content as $group_content_entity_type_id => $group_content_bundle_ids) {
      foreach ($group_content_bundle_ids as $group_content_bundle_id => $expected_result) {
        $this->assertEquals($expected_result, $this->groupTypeManager->getGroupBundleIdsByGroupContentBundle($group_content_entity_type_id, $group_content_bundle_id));
      }
    }

    // Test ::getGroupContentBundleIdsByGroupBundle().
    foreach (['node', 'block_content'] as $group_entity_type_id) {
      for ($i = 0; $i < 2; $i++) {
        $group_bundle_id = 'group_' . $i;

        // If the expected value is omitted, we expect an empty array.
        $expected_result = !empty($expected_group_content_by_group[$group_entity_type_id][$group_bundle_id]) ? $expected_group_content_by_group[$group_entity_type_id][$group_bundle_id] : [];

        $this->assertEquals($expected_result, $this->groupTypeManager->getGroupContentBundleIdsByGroupBundle($group_entity_type_id, $group_bundle_id));
      }
    }
  }

  /**
   * Provides test data for testGetBundleIdsByBundle().
   *
   * @return array
   *   An array of test properties. Each property is an indexed array with the
   *   following items:
   *   - An array indicating the relationships between groups and group content
   *     bundles that need to be set up in the test.
   *   - An array containing the expected results for the call to
   *     getGroupBundleIdsByGroupContentBundle().
   *   - An array containing the expected results for the 4 calls to
   *     getGroupContentBundleIdsByGroupBundle() that will be made in the test.
   *     If an empty array is expected to be returned, this result is omitted.
   */
  public function getBundleIdsByBundleProvider() {
    return [
      // Test the simplest case: a single group content type that references a
      // single group type.
      [
        // The first parameter sets up the relations between groups and group
        // content.
        [
          // Creating group content of type 'node'.
          'node' => [
            // The first of which...
            'group_content_0' => [
              // Has a single group audience field, configured to reference
              // groups of type 'node', targeting bundle '0'.
              ['node' => ['group_0']],
            ],
          ],
        ],
        // The second parameter contains the expected result for the call to
        // getGroupBundleIdsByGroupContentBundle(). In this case we expect group
        // '0' of type 'node' to be referenced.
        [
          'node' => [
            'group_content_0' => [
              'node' => ['group_0' => 'group_0'],
            ],
          ],
        ],
        // Finally, the third parameter contains all 4 expected results for the
        // call to getGroupContentBundleIdsByGroupBundle(). In this test only
        // node 0 should be referenced, all others should be empty.
        // Note that if the result is expected to be an empty array it can be
        // omitted from this list. In reality all 4 possible permutations will
        // always be tested.
        [
          // When calling the method with entity type 'node' and bundle '0' we
          // expect an array to be returned containing group content of type
          // 'node', bundle '0'.
          'node' => [
            'group_0' => ['node' => ['group_content_0' => 'group_content_0']],
            // There is no group content referencing group '1', so we expect an
            // empty array. This may be omitted.
            'group_1' => [],
          ],
          'block_content' => [
            // This may be omitted.
            'group_0' => [],
            // This may be omitted.
            'group_1' => [],
          ],
        ],
      ],

      // When the bundles are left empty, all bundles should be referenced.
      [
        // Group to group content relationship matrix.
        [
          'node' => [
            'group_content_0' => [
              ['node' => []],
            ],
          ],
        ],
        // Expected result for getGroupBundleIdsByGroupContentBundle().
        [
          'node' => [
            'group_content_0' => [
              'node' => ['group_0' => 'group_0', 'group_1' => 'group_1'],
            ],
          ],
        ],
        // Expected result for getGroupContentBundleIdsByGroupBundle().
        [
          'node' => [
            'group_0' => ['node' => ['group_content_0' => 'group_content_0']],
            'group_1' => ['node' => ['group_content_0' => 'group_content_0']],
          ],
        ],
      ],

      // Test having two group audience fields referencing both group types.
      [
        // Group to group content relationship matrix.
        [
          'node' => [
            'group_content_0' => [
              ['node' => []],
              ['block_content' => ['group_0', 'group_1']],
            ],
          ],
        ],
        // Expected result for getGroupBundleIdsByGroupContentBundle().
        [
          'node' => [
            'group_content_0' => [
              'node' => ['group_0' => 'group_0', 'group_1' => 'group_1'],
              'block_content' => ['group_0' => 'group_0', 'group_1' => 'group_1'],
            ],
          ],
        ],
        // Expected result for getGroupContentBundleIdsByGroupBundle().
        [
          'node' => [
            'group_0' => ['node' => ['group_content_0' => 'group_content_0']],
            'group_1' => ['node' => ['group_content_0' => 'group_content_0']],
          ],
          'block_content' => [
            'group_0' => ['node' => ['group_content_0' => 'group_content_0']],
            'group_1' => ['node' => ['group_content_0' => 'group_content_0']],
          ],
        ],
      ],

      // Test having two group audience fields, one referencing node group 0 and
      // the other entity test group 1.
      [
        // Group to group content relationship matrix.
        [
          'node' => [
            'group_content_0' => [
              ['node' => ['group_0']],
              ['block_content' => ['group_1']],
            ],
          ],
        ],
        // Expected result for getGroupBundleIdsByGroupContentBundle().
        [
          'node' => [
            'group_content_0' => [
              'node' => ['group_0' => 'group_0'],
              'block_content' => ['group_1' => 'group_1'],
            ],
          ],
        ],
        // Expected result for getGroupContentBundleIdsByGroupBundle().
        [
          'node' => [
            'group_0' => ['node' => ['group_content_0' => 'group_content_0']],
          ],
          'block_content' => [
            'group_1' => ['node' => ['group_content_0' => 'group_content_0']],
          ],
        ],
      ],

      // Test having two different group content entity types referencing the
      // same group.
      [
        // Group to group content relationship matrix.
        [
          'node' => [
            'group_content_0' => [
              ['node' => ['group_0']],
            ],
          ],
          'block_content' => [
            'group_content_0' => [
              ['node' => ['group_0']],
            ],
          ],
        ],
        // Expected result for getGroupBundleIdsByGroupContentBundle().
        [
          'node' => [
            'group_content_0' => [
              'node' => ['group_0' => 'group_0'],
            ],
          ],
          'block_content' => [
            'group_content_0' => [
              'node' => ['group_0' => 'group_0'],
            ],
          ],
        ],
        // Expected result for getGroupContentBundleIdsByGroupBundle().
        [
          'node' => [
            'group_0' => [
              'node' => ['group_content_0' => 'group_content_0'],
              'block_content' => ['group_content_0' => 'group_content_0'],
            ],
          ],
        ],
      ],

      // Test having two identical group audience fields on the same group
      // content type.
      [
        // Group to group content relationship matrix.
        [
          'node' => [
            'group_content_0' => [
              ['node' => ['group_0']],
              ['node' => ['group_0']],
            ],
          ],
        ],
        // Expected result for getGroupBundleIdsByGroupContentBundle().
        [
          'node' => [
            'group_content_0' => [
              'node' => ['group_0' => 'group_0'],
            ],
          ],
        ],
        // Expected result for getGroupContentBundleIdsByGroupBundle().
        [
          'node' => [
            'group_0' => ['node' => ['group_content_0' => 'group_content_0']],
          ],
        ],
      ],

      // Test having two group audience fields on the same group content type,
      // each referencing a different group bundle of the same type.
      [
        // Group to group content relationship matrix.
        [
          'node' => [
            'group_content_0' => [
              ['node' => ['group_0']],
              ['node' => ['group_1']],
            ],
          ],
        ],
        // Expected result for getGroupBundleIdsByGroupContentBundle().
        [
          'node' => [
            'group_content_0' => [
              'node' => ['group_0' => 'group_0', 'group_1' => 'group_1'],
            ],
          ],
        ],
        // Expected result for getGroupContentBundleIdsByGroupBundle().
        [
          'node' => [
            'group_0' => ['node' => ['group_content_0' => 'group_content_0']],
            'group_1' => ['node' => ['group_content_0' => 'group_content_0']],
          ],
        ],
      ],

      // Test having two group content types referencing the same group. The
      // second group content type also references another group with a second
      // group audience field.
      [
        // Group to group content relationship matrix.
        [
          'node' => [
            'group_content_0' => [
              ['node' => ['group_0']],
            ],
            'group_content_1' => [
              ['node' => ['group_0']],
              ['node' => ['group_1']],
            ],
          ],
        ],
        // Expected result for getGroupBundleIdsByGroupContentBundle().
        [
          'node' => [
            'group_content_0' => [
              'node' => ['group_0' => 'group_0'],
            ],
            'group_content_1' => [
              'node' => ['group_0' => 'group_0', 'group_1' => 'group_1'],
            ],
          ],
        ],
        // Expected result for getGroupContentBundleIdsByGroupBundle().
        [
          'node' => [
            'group_0' => [
              'node' => [
                'group_content_0' => 'group_content_0',
                'group_content_1' => 'group_content_1',
              ],
            ],
            'group_1' => ['node' => ['group_content_1' => 'group_content_1']],
          ],
        ],
      ],

      // Bananas.
      [
        // Group to group content relationship matrix.
        [
          'node' => [
            'group_content_0' => [
              0 => ['node' => ['group_0']],
              1 => ['block_content' => ['group_0', 'group_1']],
            ],
            'group_content_1' => [
              2 => ['block_content' => ['group_1']],
            ],
          ],
          'block_content' => [
            'group_content_2' => [
              0 => ['node' => ['group_0']],
              1 => ['node' => ['group_0']],
              2 => ['node' => ['group_1']],
            ],
            'group_content_3' => [
              3 => ['block_content' => ['group_0', 'group_1']],
            ],
            'group_content_4' => [
              4 => ['node' => ['group_0', 'group_1']],
              5 => ['block_content' => ['group_1']],
            ],
          ],
        ],
        // Expected result for getGroupBundleIdsByGroupContentBundle().
        [
          'node' => [
            'group_content_0' => [
              'node' => ['group_0' => 'group_0'],
              'block_content' => ['group_0' => 'group_0', 'group_1' => 'group_1'],
            ],
            'group_content_1' => [
              'block_content' => ['group_1' => 'group_1'],
            ],
          ],
          'block_content' => [
            'group_content_2' => [
              'node' => ['group_0' => 'group_0', 'group_1' => 'group_1'],
            ],
            'group_content_3' => [
              'block_content' => ['group_0' => 'group_0', 'group_1' => 'group_1'],
            ],
            'group_content_4' => [
              'node' => ['group_0' => 'group_0', 'group_1' => 'group_1'],
              'block_content' => ['group_1' => 'group_1'],
            ],
          ],
        ],
        // Expected result for getGroupContentBundleIdsByGroupBundle().
        [
          'node' => [
            'group_0' => [
              'node' => ['group_content_0' => 'group_content_0'],
              'block_content' => [
                'group_content_2' => 'group_content_2',
                'group_content_4' => 'group_content_4',
              ],
            ],
            'group_1' => [
              'block_content' => [
                'group_content_2' => 'group_content_2',
                'group_content_4' => 'group_content_4',
              ],
            ],
          ],
          'block_content' => [
            'group_0' => [
              'node' => ['group_content_0' => 'group_content_0'],
              'block_content' => ['group_content_3' => 'group_content_3'],
            ],
            'group_1' => [
              'node' => [
                'group_content_0' => 'group_content_0',
                'group_content_1' => 'group_content_1',
              ],
              'block_content' => [
                'group_content_3' => 'group_content_3',
                'group_content_4' => 'group_content_4',
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests that retrieval of group content bundle IDs uses cached data.
   *
   * @covers ::getGroupContentBundleIdsByGroupBundle
   */
  public function testGetGroupContentBundleIdsByGroupBundleUsesCachedData() {
    // Initially no cached group relation data should exist.
    $this->assertNull($this->getCachedGroupRelationMap());

    // We do not yet have any group content types, so when we retrieve the group
    // content bundle IDs we should get no result back, and the cache should
    // remain empty.
    $bundle_ids = $this->groupTypeManager->getGroupContentBundleIdsByGroupBundle('node', 'group_0');
    $this->assertEquals([], $bundle_ids);

    // The cached group relation data should now no longer be NULL but an empty
    // array. NULL indicates the absence of cached data.
    $this->assertEquals([], $this->getCachedGroupRelationMap());

    // Reset the data, this should clear the cached data again.
    $this->groupTypeManager->reset();
    $this->assertNull($this->getCachedGroupRelationMap());

    // Inject some data in the cache, and check that the group type manager uses
    // this cached data.
    $relation_data = [
      'node' => [
        'group_0' => [
          'group_content_entity_type_id' => [
            'group_content_bundle_id',
          ],
        ],
      ],
    ];
    $this->cacheGroupRelationMap($relation_data);

    $bundle_ids = $this->groupTypeManager->getGroupContentBundleIdsByGroupBundle('node', 'group_0');
    $this->assertEquals($relation_data['node']['group_0'], $bundle_ids);
  }

  /**
   * Returns the group relation map from the cache.
   *
   * @return array|null
   *   An associative array representing group and group content relations, or
   *   NULL if the group relation map was not found in the cache.
   */
  protected function getCachedGroupRelationMap(): ?array {
    return $this->cache->get(GroupTypeManager::GROUP_RELATION_MAP_CACHE_KEY)->data ?? NULL;
  }

  /**
   * Stores the group relation map in the cache.
   *
   * @param array $relation_data
   *   An associative array representing group and group content relations.
   */
  protected function cacheGroupRelationMap(array $relation_data): void {
    $this->cache->set(GroupTypeManager::GROUP_RELATION_MAP_CACHE_KEY, $relation_data);
  }

}
