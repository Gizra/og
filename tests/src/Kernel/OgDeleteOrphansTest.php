<?php

namespace Drupal\Tests\og\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\user\Entity\User;

/**
 * Tests deletion of orphaned group content and memberships.
 *
 * @group og
 */
class OgDeleteOrphansTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'entity_reference',
    'node',
    'og',
  ];

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
   * A test group content.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $groupContent;

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
    $this->installSchema('system', ['sequences']);

    /** @var \Drupal\og\OgDeleteOrphansPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.og.delete_orphans');
    $this->ogDeleteOrphansPluginManager = $plugin_manager;

    // Create a group entity type.
    $group_bundle = mb_strtolower($this->randomMachineName());
    NodeType::create([
      'type' => $group_bundle,
      'name' => $this->randomString(),
    ])->save();
    Og::groupTypeManager()->addGroup('node', $group_bundle);

    // Create a group content entity type.
    $group_content_bundle = mb_strtolower($this->randomMachineName());
    NodeType::create([
      'type' => $group_content_bundle,
      'name' => $this->randomString(),
    ])->save();
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', $group_content_bundle);

    // Create group admin user.
    $group_admin = User::create(['name' => $this->randomString()]);
    $group_admin->save();

    // Create a group.
    $this->group = Node::create([
      'title' => $this->randomString(),
      'type' => $group_bundle,
      'uid' => $group_admin->id(),
    ]);
    $this->group->save();

    // Create a group content item.
    $this->groupContent = Node::create([
      'title' => $this->randomString(),
      'type' => $group_content_bundle,
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => [['target_id' => $this->group->id()]],
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
   * @param bool $run_shutdown_functions
   *   Whether or not shutdown functions should be run as part of the test.
   * @param bool $asynchronous
   *   Whether or not the actual deletion of the orphans happens in an
   *   asynchronous operation (e.g. pressing the button that launches the batch
   *   process).
   * @param string $queue_id
   *   The ID of the queue that is used by the plugin under test.
   *
   * @dataProvider ogDeleteOrphansPluginProvider
   */
  public function testDeleteOrphans($plugin_id, $run_cron, $run_shutdown_functions, $asynchronous, $queue_id) {
    // Turn on deletion of orphans in the configuration and configure the chosen
    // plugin.
    $this->config('og.settings')
      ->set('delete_orphans', TRUE)
      ->set('delete_orphans_plugin_id', $plugin_id)
      ->save();

    // Check that the queue is initially empty.
    $this->assertQueueCount($queue_id, 0);

    // Check that the group owner has initially been subscribed to the group.
    $this->assertUserMembershipCount(1);

    // Delete the group.
    $this->group->delete();

    // Check that 2 orphans are queued for asynchronous processing: 1 group
    // content item and 1 user membership.
    if ($asynchronous) {
      $this->assertQueueCount($queue_id, 2);
    }

    // Run cron jobs if needed.
    if ($run_cron) {
      $this->container->get('cron')->run();
    }

    // Run shutdown functions if needed.
    if ($run_shutdown_functions) {
      _drupal_shutdown_function();
    }

    // Simulate the initiation of the queue process by an asynchronous operation
    // (such as pressing the button that starts a batch operation).
    if ($asynchronous) {
      $this->process($queue_id, $plugin_id);
    }

    // Verify the group content is deleted.
    $this->assertNull(Node::load($this->groupContent->id()), 'The orphaned node is deleted.');

    // Verify that the user membership is now deleted.
    $this->assertUserMembershipCount(0);

    // Check that the queue is now empty.
    $this->assertQueueCount($queue_id, 0);
  }

  /**
   * Tests that orphaned content is not deleted when the option is disabled.
   *
   * @param string $plugin_id
   *   The machine name of the plugin under test.
   * @param bool $run_cron
   *   Whether or not cron jobs should be run as part of the test. Unused in
   *   this test.
   * @param bool $asynchronous
   *   Whether or not the actual deletion of the orphans happens in an
   *   asynchronous operation (e.g. pressing the button that launches the batch
   *   process). Unused in this test.
   * @param string $queue_id
   *   The ID of the queue that is used by the plugin under test.
   *
   * @dataProvider ogDeleteOrphansPluginProvider
   */
  public function testDisabled($plugin_id, $run_cron, $asynchronous, $queue_id) {
    // Disable deletion of orphans in the configuration and configure the chosen
    // plugin.
    $this->config('og.settings')
      ->set('delete_orphans', FALSE)
      ->set('delete_orphans_plugin_id', $plugin_id)
      ->save();

    // Delete the group.
    $this->group->delete();

    // Check that no orphans are queued for deletion.
    $this->assertQueueCount($queue_id, 0);
  }

  /**
   * Provides OgDeleteOrphans plugins for the tests.
   *
   * @return array
   *   An array of test properties. Each property is an indexed array with the
   *   following items:
   *   - A string containing the plugin name being tested.
   *   - A boolean indicating whether or not cron jobs should be run.
   *   - A boolean indicating whether or not shutdown functions should be run.
   *   - A boolean indicating whether the deletion happens in an asynchronous
   *     process.
   *   - A string defining the queue that is used by the plugin.
   */
  public function ogDeleteOrphansPluginProvider() {
    return [
      ['batch', FALSE, FALSE, TRUE, 'og_orphaned_group_content'],
      ['cron', TRUE, FALSE, FALSE, 'og_orphaned_group_content_cron'],
      ['simple', FALSE, TRUE, FALSE, 'og_orphaned_group_content'],
    ];
  }

  /**
   * Returns the number of items a given queue contains.
   *
   * @param string $queue_id
   *   The ID of the queue for which to count the items.
   */
  protected function getQueueCount($queue_id) {
    return $this->container->get('queue')->get($queue_id)->numberOfItems();
  }

  /**
   * Checks that the given queue contains the expected number of items.
   *
   * @param string $queue_id
   *   The ID of the queue to check.
   * @param int $count
   *   The expected number of items in the queue.
   */
  protected function assertQueueCount($queue_id, $count) {
    $this->assertEquals($count, $this->getQueueCount($queue_id));
  }

  /**
   * Checks the number of user memberships.
   *
   * @param int $expected
   *   The expected number of user memberships.
   */
  protected function assertUserMembershipCount($expected) {
    $count = \Drupal::entityQuery('og_membership')->count()->execute();
    $this->assertEquals($expected, $count);
  }

  /**
   * Processes the given queue.
   *
   * @param string $queue_id
   *   The ID of the queue to process.
   * @param string $plugin_id
   *   The ID of the plugin that is responsible for processing the queue.
   */
  protected function process($queue_id, $plugin_id) {
    /** @var \Drupal\og\OgDeleteOrphansInterface $plugin */
    $plugin = $this->ogDeleteOrphansPluginManager->createInstance($plugin_id, []);
    while ($this->getQueueCount($queue_id) > 0) {
      $plugin->process();
    }
  }

}
