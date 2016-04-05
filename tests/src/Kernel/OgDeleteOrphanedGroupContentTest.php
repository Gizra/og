<?php

namespace Drupal\Tests\og\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;

/**
 * Tests deletion of orphaned group content.
 *
 * @group og
 */
class OgDeleteOrphanedGroupContentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'field', 'entity_reference', 'node', 'og'];

  /**
   * The plugin manager for OgDeleteOrphans plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $ogDeleteOrphansPluginManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->installSchema('system', ['queue', 'sequences']);

    /** @var \Drupal\og\OgDeleteOrphansPluginManager ogDeleteOrphansPluginManager */
    $this->ogDeleteOrphansPluginManager = \Drupal::service('plugin.manager.og.delete_orphans');

    // Create a group entity type.
    $this->groupBundle = Unicode::strtolower($this->randomMachineName());
    NodeType::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
    ])->save();
    Og::groupManager()->addGroup('node', $this->groupBundle);

    // Create a group content entity type.
    $this->groupContentBundle = Unicode::strtolower($this->randomMachineName());
    NodeType::create([
      'type' => $this->groupContentBundle,
      'name' => $this->randomString(),
    ])->save();
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', $this->groupContentBundle);

    // Create a group.
    $this->group = Node::create([
      'title' => $this->randomString(),
      'type' => $this->groupBundle,
    ]);
    $this->group->save();

    // Create a group content item.
    $this->groupContent = Node::create([
      'title' => $this->randomString(),
      'type' => $this->groupContentBundle,
      OgGroupAudienceHelper::DEFAULT_FIELD => [['target_id' => $this->group->id()]],
    ]);
    $this->groupContent->save();
  }

  /**
   * Tests that orphaned group content is deleted when the group is deleted.
   *
   * @param string $plugin_id
   *   The machine name of the plugin under test.
   * @param bool $run_cron
   *   Whether or not cron jobs should be run as part of the test.
   * @param string $queue_id
   *   The ID of the queue that is used by the plugin under test.
   *
   * @dataProvider ogDeleteOrphansPluginProvider
   */
  public function testDeleteOrphans($plugin_id, $run_cron, $queue_id) {
    // Turn on deletion of orphans in the configuration and configure the chosen
    // plugin.
    $this->config('og.settings')
      ->set('delete_orphans', TRUE)
      ->set('delete_orphans_plugin_id', $plugin_id)
      ->save();

    // Delete the group.
    $this->group->delete();

    // Check that the orphan is queued.
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->container->get('queue')->get($queue_id);
    $this->assertEquals(1, $queue->numberOfItems());

    // Run cron jobs if needed.
    if ($run_cron) {
      $this->container->get('cron')->run();
    }

    // Invoke the processing of the orphans.
    /** @var \Drupal\og\OgDeleteOrphansInterface $plugin */
    $plugin = $this->ogDeleteOrphansPluginManager->createInstance($plugin_id, []);
    $plugin->process();

    // Verify the group content is deleted.
    $this->assertFalse($this->group_content, 'The orphaned node is deleted.');

    // Check that the queue is now empty.
    $this->assertEquals(0, $queue->numberOfItems());
  }

  /**
   * Tests that orphaned content is not deleted when the option is disabled.
   *
   * @param string $plugin_id
   *   The machine name of the plugin under test.
   * @param bool $run_cron
   *   Whether or not cron jobs should be run as part of the test. Unused in
   *   this test.
   * @param string $queue_id
   *   The ID of the queue that is used by the plugin under test.
   *
   * @dataProvider ogDeleteOrphansPluginProvider
   */
  function testDisabled($plugin_id, $run_cron, $queue_id) {
    // Disable deletion of orphans in the configuration and configure the chosen
    // plugin.
    $this->config('og.settings')
      ->set('delete_orphans', FALSE)
      ->set('delete_orphans_plugin_id', $plugin_id)
      ->save();

    // Delete the group.
    $this->group->delete();

    // Check that no orphans are queued for deletion.
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->container->get('queue')->get($queue_id);
    $this->assertEquals(0, $queue->numberOfItems());
  }

  /**
   * Provides OgDeleteOrphans plugins for the tests.
   *
   * @return array
   *   An array of test properties. Each property is an indexed array with the
   *   following items:
   *   - A string containing the plugin name being tested.
   *   - A boolean indicating whether or not cron jobs should be run.
   *   - A string defining the queue that is used by the plugin.
   */
  public function ogDeleteOrphansPluginProvider() {
    return [
      ['batch', FALSE, 'og_orphaned_group_content'],
      ['cron', TRUE, 'og_orphaned_group_content_cron'],
      ['simple', FALSE, 'og_orphaned_group_content'],
    ];
  }

  /**
   * Tests the moving of the node to another group when deleting a group.
   *
   * @todo This test doesn't make any sense to me. The way I read it is that if
   *   multiple groups are present and one of the groups is deleted then its
   *   content is expected to be moved into a random other group? This seems
   *   dangerous, it might expose private data.
   *
   *   This might be useful for child groups that are related to parent groups,
   *   so that when a child group is deleted its content will be moved to the
   *   parent, but then there should be a very clear indication of the parent-
   *   child relation which is missing in this test. Here the content just moves
   *   to whatever random group that is available.
   */
  function _testMoveOrphans() {
    // Creating two groups.
    $first_group = $this->drupalCreateNode(array('type' => $this->group_type, 'title' => 'move'));
    $second_group = $this->drupalCreateNode(array('type' => $this->group_type));

    // Create a group and relate it to the first group.
    $first_node = $this->drupalCreateNode(array('type' => $this->node_type));
    og_group('node', $first_group, array('entity_type' => 'node', 'entity' => $first_node));

    // Delete the group.
    node_delete($first_group->nid);

    // Execute manually the queue worker.
    $queue = DrupalQueue::get('og_membership_orphans');
    $item = $queue->claimItem();
    og_membership_orphans_worker($item->data);

    // Load the node into a wrapper and verify we moved him to another group.
    $gids = og_get_entity_groups('node', $first_node->nid);
    $gid = reset($gids['node']);

    $this->assertEqual($gid, $second_group->nid, 'The group content moved to another group.');
  }

}
