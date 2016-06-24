<?php

namespace Drupal\Tests\og\Kernel;

use Drupal\Core\Entity\Entity;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\og\Event\PermissionEvent;
use Drupal\og\Event\PermissionEventInterface;

/**
 * Tests the implementations of the PermissionEvent in 'og' and 'og_ui'.
 *
 * @group og
 */
class PermissionEventTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'node',
    'og',
    'og_ui',
    'system',
    'user',
  ];

  /**
   * The Symfony event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The bundle ID used for the test group.
   *
   * @var string
   */
  protected $groupBundleId;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->eventDispatcher = $this->container->get('event_dispatcher');

    // Create a group content entity type. The type and name of this bundle are
    // used to create the group content permissions.
    NodeType::create([
      'type' => 'test_group_content',
      'name' => 'Test Group Content',
    ])->save();
  }

  /**
   * Tests that the two OG modules can provide their own OG permissions.
   *
   * Some permissions (such as 'subscribe', 'manage members', etc.) are
   * available for all group types. In addition to this there are also OG
   * permissions for creating, editing and deleting the group content that
   * associated with the group.
   *
   * In this test we will check that the correct permissions are generated for
   * our test group (which includes permissions to create, edit and delete group
   * content of type 'test_group_content'), as well as a control group which
   * doesn't have any group content - in this case it should only return the
   * default permissions that are available to all group types.
   *
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs that are associated with the test
   *   group.
   * @param array $expected_permissions
   *   An array of permission names that are expected to be returned.
   * @param array $expected_full_permissions
   *   An array of full permissions that are expected to be returned. This is a
   *   subset of the permissions. It is not necessary to test the full
   *   permission data for each entry, testing the data for only a couple of
   *   permissions is sufficient.
   *
   * @dataProvider permissionEventDataProvider
   */
  public function testPermissionEventIntegration(array $group_content_bundle_ids, array $expected_permissions, $expected_full_permissions) {
    // Retrieve the permissions from the listeners.
    /** @var PermissionEvent $permission_event */
    $event = new PermissionEvent($this->randomMachineName(), $this->randomMachineName(), $group_content_bundle_ids);
    $permission_event = $this->eventDispatcher->dispatch(PermissionEventInterface::EVENT_NAME, $event);
    $actual_permissions = array_keys($permission_event->getPermissions());

    // Sort the permission arrays so they can be compared.
    sort($expected_permissions);
    sort($actual_permissions);

    $this->assertEquals($expected_permissions, $actual_permissions);

    // When testing the group content bundles, check that the bundle info has
    // been correctly retrieved from the group content bundle that was created
    // in the setUp() and used to create the permissions.
    foreach ($expected_full_permissions as $permission) {
      $this->assertArraySubset($permission, $permission_event->getPermissions());
    }
  }

  /**
   * Provides expected results for the testPermissionEventIntegration test.
   *
   * @return array
   *   An array of test properties. Each property is an indexed array with the
   *   following items:
   *   - An array of group content bundle IDs that are associated with the
   *     test group. Used to check if group content permissions are correctly
   *     provided.
   *   - An array of permission names that are expected to be returned. Used to
   *     check that the correct permissions are returned.
   *   - An array of full permissions that are expected to be returned. This is
   *     a subset of the permissions. It is not necessary to test the full
   *     permission data for each entry, testing the data for only 1 or 2
   *     permissions is sufficient.
   */
  public function permissionEventDataProvider() {
    // Test permissions that should be available for both test groups.
    $default_permissions = [
      'add user',
      'administer group',
      'approve and deny subscription',
      'manage members',
      'manage permissions',
      'manage roles',
      'subscribe without approval',
      'subscribe',
      'unsubscribe',
      'update group',
    ];
    // Test permissions that should only be available for the test group that
    // has group content.
    $group_content_permissions = [
      'create test_group_content node',
      'delete any test_group_content node',
      'delete own test_group_content node',
      'update any test_group_content node',
      'update own test_group_content node',
    ];
    // A full permission that should be available in both test groups. This is
    // used to test that all properties are correctly applied.
    $group_level_permission = [
      'administer group' => [
        'title' => $this->t('Administer group'),
        'description' => $this->t('Manage group members and content in the group.'),
        'default roles' => ['administrator'],
        'restrict access' => TRUE,
      ],
    ];
    // A full permission that should only be available for the test group that
    // has group content.
    $group_content_operation_permission = [
      'create test_group_content node' => [
        'title' => $this->t('Create %bundle @entity', [
          '%bundle' => 'Test Group Content',
          '@entity' => 'content items',
        ]),
        'default role' => ['administrator'],
      ]
    ];
    return [
      // Test retrieving permissions for a group that has no group content types
      // associated with it.
      [
        [],
        // It should only return the default permissions.
        $default_permissions,
        // The list of permissions should only contain the group level
        // permission ('administer group'). and the group content permission
        // ('create test_group_content node').
        [
          $group_level_permission,
        ],
      ],
      // Test retrieving permissions for a group that has a group content type
      // associated with it.
      [
        [
          'node' => ['test_group_content'],
        ],
        // It should return the default permissions as well as the permissions
        // to create, delete and update group content.
        array_merge($default_permissions, $group_content_permissions),
        // The list of permissions should contain both the group level
        // permission ('administer group') and the group content permission
        // ('create test_group_content node').
        [
          $group_level_permission,
          $group_content_operation_permission,
        ],
      ],
    ];
  }

  /**
   * Implementation of the global t() function.
   *
   * The global t() function is not available in scope of the data provider, so
   * it is replicated here.
   *
   * @see t()
   */
  function t($string, array $args = array(), array $options = array()) {
    return new TranslatableMarkup($string, $args, $options);
  }

}
