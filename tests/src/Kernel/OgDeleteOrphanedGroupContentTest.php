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
   * A test group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $group;

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
    $group_bundle = Unicode::strtolower($this->randomMachineName());
    NodeType::create([
      'type' => $group_bundle,
      'name' => $this->randomString(),
    ])->save();
    Og::groupManager()->addGroup('node', $group_bundle);

    // Create a group content entity type.
    $group_content_bundle = Unicode::strtolower($this->randomMachineName());
    NodeType::create([
      'type' => $group_content_bundle,
      'name' => $this->randomString(),
    ])->save();
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', $group_content_bundle);

    // Create a group.
    $this->group = Node::create([
      'title' => $this->randomString(),
      'type' => $group_bundle,
    ]);
    $this->group->save();

    // Create a group content item.
    $group_content = Node::create([
      'title' => $this->randomString(),
      'type' => $group_content_bundle,
      OgGroupAudienceHelper::DEFAULT_FIELD => [['target_id' => $this->group->id()]],
    ]);
    $group_content->save();
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

}
